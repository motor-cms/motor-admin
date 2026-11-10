<?php

namespace Motor\Admin\Services\AI\Builder;

use Motor\Admin\Services\AI\MistralClient;

/**
 * Prompt + catalog manifest -> validated intermediate JSON (sections).
 *
 * One strict json_schema call, then up to N repair rounds in which the
 * validator's error list is sent back. After the last round invalid sections
 * are dropped; if nothing valid is left, AIOutputInvalidException is thrown.
 *
 * With content suggestions on (`content`), the model fills every text field with
 * real copy, writes a search term and alt text per image, and may only link to
 * the pages it was given. Afterwards each image term is looked up in the
 * tenant's media library; the best unused hit is returned with the section.
 */
class SectionGenerator
{
    /** @var array<int, true> media ids already used in this response */
    protected array $usedMedia = [];

    public function __construct(protected MistralClient $client, protected MediaSearch $mediaSearch) {}

    /**
     * @param  array<string, mixed>  $manifest
     * @param  array<string, mixed>  $context  page_title?, page_outline?: string[], tone?
     * @return array{data: array{sections: array, placeholders: array}, meta: array}
     */
    public function generate(
        string $prompt,
        array $manifest,
        array $context = [],
        int $maxSections = 3,
        ?string $model = null,
        ?string $tonePrompt = null,
        bool $content = false,
        array $linkTargets = [],
        ?int $clientId = null,
        array $ownNames = [],
    ): array {
        $model ??= (string) config('ai.mistral.models.builder');
        $manifest = self::contentManifest($manifest);
        $policy = new ContentPolicy((array) config('ai.builder.third_party_names', []), $ownNames);
        $started = hrtime(true);
        $refused = $policy->checkPrompt($prompt);
        if ($refused !== null) {
            throw new AIContentRefusedException($refused, ['model' => $model, 'duration_ms' => 0, 'usage' => [], 'repair_rounds' => 0]);
        }
        $validator = new SectionValidator($manifest, $maxSections, $content);
        $linkTargets = $this->cleanLinkTargets($linkTargets);
        $this->usedMedia = [];

        $messages = [
            ['role' => 'system', 'content' => $this->systemPrompt($manifest, $tonePrompt, $content)],
            ['role' => 'user', 'content' => $this->userPrompt($prompt, $context, $maxSections, $content ? $linkTargets : null)],
        ];
        $payload = [
            'model'           => $model,
            'temperature'     => 0.4,
            'max_tokens'      => (int) config('ai.builder.max_output_tokens', 6000),
            'response_format' => [
                'type'        => 'json_schema',
                'json_schema' => [
                    'name'   => 'builder_sections',
                    'strict' => true,
                    'schema' => SectionSchema::forManifest($manifest, $maxSections, $content),
                ],
            ],
        ];

        $run = $this->complete($payload, $messages, fn ($output) => $validator->validate($output));
        ['output' => $output, 'errors' => $errors, 'round' => $round, 'usage' => $usage, 'model' => $usedModel, 'refusal' => $refusal] = $run;
        $firstAttemptValid = $run['first_attempt_valid'];

        $metaFor = fn () => [
            'model'         => $usedModel,
            'repair_rounds' => $round,
            'duration_ms'   => (int) ((hrtime(true) - $started) / 1_000_000),
            'usage'         => $usage,
        ];
        if ($refusal !== null) {
            // The model's own sentence is the reason the editor sees (e.g. a block that is not in the catalog)
            throw new AIContentRefusedException('model refused: '.mb_substr($refusal, 0, 120), $metaFor(), mb_substr(self::plainText($refusal), 0, 300));
        }

        $warnings = $validator->warnings;
        $resolve = new ContentResolution($content, $linkTargets, $prompt, $clientId);
        $sections = [];
        if (is_array($output) && is_array($output['sections'] ?? null) && ! isset($errors[-1])) {
            foreach (array_values($output['sections']) as $index => $section) {
                if (isset($errors[$index])) {
                    $warnings[] = 'Abschnitt '.($index + 1).' verworfen: '.implode(' ', $errors[$index]);

                    continue;
                }
                $sections[] = $this->normalize($section, $manifest, $resolve);
            }
        }
        $refused = $policy->checkSections($sections);
        if ($refused !== null) {
            throw new AIContentRefusedException($refused, $metaFor());
        }
        $warnings = array_merge($warnings, $resolve->warnings);
        // From the content, not from the model's list (that one named placeholders missing in the text, K3)
        $placeholders = array_merge(self::placeholdersIn($sections), $resolve->placeholders);

        $meta = [
            'model'               => $usedModel,
            'repair_rounds'       => $round,
            'first_attempt_valid' => $firstAttemptValid,
            'duration_ms'         => (int) ((hrtime(true) - $started) / 1_000_000),
            'usage'               => $usage,
            'warnings'            => array_values(array_unique($warnings)),
            'content'             => $content,
            'media_found'         => $resolve->mediaFound,
            'media_missing'       => $resolve->mediaMissing,
        ];

        if ($sections === []) {
            throw new AIOutputInvalidException(array_merge(...array_values($errors ?: [[]])), $meta);
        }

        return [
            'data' => [
                'sections'     => $sections,
                'placeholders' => array_values(array_unique($placeholders)),
            ],
            'meta' => $meta,
        ];
    }

    /**
     * "Mit KI überarbeiten": existing sections (manifest format, stable refs, no uuids/classes) plus the
     * requested change -> operations keep/move/update/delete/add in the new page order.
     *
     * Scope "section": exactly one update of the target ref (the admin replaces the section with it).
     * Scope "page": the model may change, reorder, delete and add sections; locked sections and linked
     * central templates come as `locked` (only a label) and always stay "keep".
     * The same guard rails as for new sections apply: content policy, placeholders instead of invented
     * facts (numbers already on the page may stay), known link targets only, media library search for
     * new image terms. Images whose search term the model left unchanged are kept (`keep: true`).
     *
     * @param  array<int, array<string, mixed>>  $current  ref, block?, label?, locked?, fields?, lists?, media?
     * @param  array<string, mixed>  $context  page_title?, page_outline?
     * @return array{data: array{operations: array, placeholders: array}, meta: array}
     */
    public function revise(
        string $instruction,
        array $manifest,
        array $current,
        string $scope = 'page',
        ?string $target = null,
        array $context = [],
        int $maxNew = 3,
        ?string $model = null,
        ?string $tonePrompt = null,
        array $linkTargets = [],
        ?int $clientId = null,
        array $ownNames = [],
    ): array {
        $model ??= (string) config('ai.mistral.models.builder');
        $manifest = self::contentManifest($manifest);
        $policy = new ContentPolicy((array) config('ai.builder.third_party_names', []), $ownNames);
        $started = hrtime(true);
        $refused = $policy->checkPrompt($instruction);
        if ($refused !== null) {
            throw new AIContentRefusedException($refused, ['model' => $model, 'duration_ms' => 0, 'usage' => [], 'repair_rounds' => 0]);
        }

        $current = self::cleanCurrent($current, $manifest);
        $refs = array_map(fn ($c) => $c['ref'], $current);
        $editable = array_values(array_map(fn ($c) => $c['ref'], array_filter($current, fn ($c) => ! $c['locked'])));
        if ($scope === 'section') {
            if ($target === null || ! in_array($target, $editable, true)) {
                throw new \InvalidArgumentException('target section is unknown or locked');
            }
            $maxNew = 0;
        } elseif ($editable === [] && $maxNew === 0) {
            throw new \InvalidArgumentException('nothing to revise');
        }

        $validator = new SectionValidator($manifest, max(1, count($current) + $maxNew), true);
        $validator->rememberNumbers(array_map(fn ($c) => [$c['fields'], $c['lists'], $c['media']], $current));
        $linkTargets = $this->cleanLinkTargets($linkTargets);
        $this->usedMedia = [];

        $messages = [
            ['role' => 'system', 'content' => $this->systemPrompt($manifest, $tonePrompt, true, $this->reviseRules($scope, $target))],
            ['role' => 'user', 'content' => $this->reviseUserPrompt($instruction, $current, $scope, $target, $context, $maxNew, $linkTargets)],
        ];
        $payload = [
            'model'           => $model,
            'temperature'     => 0.3,
            'max_tokens'      => (int) config('ai.builder.max_output_tokens', 6000) * ($scope === 'page' ? 2 : 1),
            'response_format' => [
                'type'        => 'json_schema',
                'json_schema' => [
                    'name'   => 'builder_revision',
                    'strict' => true,
                    'schema' => SectionSchema::forRevision($manifest, $refs, $editable, $scope, $target, $maxNew),
                ],
            ],
        ];

        $run = $this->complete($payload, $messages, fn ($output) => $validator->validateRevision($output, $current, $scope, $target, $maxNew));
        $meta = [
            'model'               => $run['model'],
            'repair_rounds'       => $run['round'],
            'first_attempt_valid' => $run['first_attempt_valid'],
            'duration_ms'         => (int) ((hrtime(true) - $started) / 1_000_000),
            'usage'               => $run['usage'],
            'scope'               => $scope,
        ];
        if ($run['refusal'] !== null) {
            throw new AIContentRefusedException('model refused: '.mb_substr($run['refusal'], 0, 120), $meta, mb_substr(self::plainText($run['refusal']), 0, 300));
        }
        $output = $run['output'];
        if (! is_array($output) || ! is_array($output['operations'] ?? null) || ! array_is_list($output['operations'])) {
            throw new AIOutputInvalidException(array_merge(...array_values($run['errors'] ?: [[]])), $meta);
        }

        $warnings = $validator->warnings;
        $operations = $this->settleOperations($output['operations'], $run['errors'], $current, $scope, $target, $warnings);
        $resolve = new ContentResolution(true, $linkTargets, $instruction, $clientId);
        $byRef = collect($current)->keyBy('ref');
        $sections = [];
        foreach ($operations as $i => $op) {
            if (isset($op['section'])) {
                $operations[$i]['section'] = $this->normalize($op['section'], $manifest, $resolve, $op['op'] === 'update' ? $byRef->get($op['ref']) : null);
                $sections[] = $operations[$i]['section'];
            }
        }
        $refused = $policy->checkSections($sections);
        if ($refused !== null) {
            throw new AIContentRefusedException($refused, $meta);
        }
        $changes = array_count_values(array_map(fn ($op) => $op['op'], $operations));
        if ($scope === 'section' && ($changes['update'] ?? 0) !== 1) {
            throw new AIOutputInvalidException(array_merge(...array_values($run['errors'] ?: [['Keine gültige Überarbeitung.']])), $meta);
        }
        if ($scope === 'page' && array_filter($operations, fn ($op) => $op['op'] !== 'delete') === []) {
            throw new AIOutputInvalidException(['Die Seite braucht mindestens einen Abschnitt.'], $meta);
        }
        $warnings = array_merge($warnings, $resolve->warnings);

        return [
            'data' => [
                'operations'   => $operations,
                'placeholders' => array_values(array_unique(array_merge(self::placeholdersIn($sections), $resolve->placeholders))),
            ],
            'meta' => $meta + [
                'warnings'      => array_values(array_unique($warnings)),
                'content'       => true,
                'changes'       => (object) $changes,
                'media_found'   => $resolve->mediaFound,
                'media_missing' => $resolve->mediaMissing,
            ],
        ];
    }

    /**
     * Context sections as the model sees them: unknown blocks and locked sections only with ref and label.
     *
     * @param  array<int, mixed>  $current
     * @param  array<string, mixed>  $manifest
     * @return array<int, array{ref: string, block: ?string, label: string, locked: bool, fields: array, lists: array, media: array}>
     */
    public static function cleanCurrent(array $current, array $manifest): array
    {
        $blocks = collect($manifest['blocks'] ?? [])->keyBy('id');
        $out = [];
        $seen = [];
        foreach (array_slice(array_values($current), 0, 40) as $section) {
            if (! is_array($section)) {
                continue;
            }
            $ref = is_scalar($section['ref'] ?? null) ? (string) $section['ref'] : '';
            if (! preg_match('/^[A-Za-z0-9_-]{1,20}$/', $ref) || isset($seen[$ref])) {
                continue;
            }
            $seen[$ref] = true;
            $block = is_string($section['block'] ?? null) && $blocks->has($section['block']) ? $section['block'] : null;
            $locked = ! empty($section['locked']) || $block === null;
            $out[] = [
                'ref'    => $ref,
                'block'  => $locked ? null : $block,
                'label'  => mb_substr(self::plainText((string) ($section['label'] ?? '')), 0, 200),
                'locked' => $locked,
                'fields' => $locked ? [] : self::limitStrings((array) ($section['fields'] ?? [])),
                'lists'  => $locked ? [] : self::limitStrings((array) ($section['lists'] ?? [])),
                'media'  => $locked ? [] : self::limitStrings(array_values((array) ($section['media'] ?? []))),
            ];
        }

        return $out;
    }

    /**
     * @return array<mixed>
     */
    protected static function limitStrings(array $value, int $depth = 0): array
    {
        if ($depth > 6) {
            return [];
        }
        foreach ($value as $key => $item) {
            if (is_string($item)) {
                $value[$key] = mb_substr($item, 0, 4000);
            } elseif (is_array($item)) {
                $value[$key] = self::limitStrings($item, $depth + 1);
            } elseif (! is_scalar($item) && $item !== null) {
                unset($value[$key]);
            }
        }

        return $value;
    }

    /**
     * Operations the admin can apply even if the last round still had errors: an invalid update stays the
     * old section, an invalid or extra add is dropped, locked sections are always kept, a missing ref is
     * kept at its old place.
     *
     * @param  array<int, mixed>  $ops
     * @param  array<int, string[]>  $errors
     * @param  array<int, array<string, mixed>>  $current
     * @param  string[]  $warnings
     * @return array<int, array{op: string, ref: string, section?: array}>
     */
    protected function settleOperations(array $ops, array $errors, array $current, string $scope, ?string $target, array &$warnings): array
    {
        $byRef = collect($current)->keyBy('ref');
        $out = [];
        $seen = [];
        foreach ($ops as $index => $op) {
            if (! is_array($op)) {
                continue;
            }
            $kind = $op['op'] ?? null;
            $ref = is_scalar($op['ref'] ?? null) ? (string) $op['ref'] : '';
            $invalid = isset($errors[$index]);
            if ($kind === 'add') {
                if ($scope === 'section' || $invalid || ! is_array($op['section'] ?? null)) {
                    if ($invalid) {
                        $warnings[] = 'Neuer Abschnitt '.($index + 1).' verworfen: '.implode(' ', $errors[$index]);
                    }

                    continue;
                }
                $newRef = $ref !== '' && ! $byRef->has($ref) && ! isset($seen[$ref]) ? $ref : 'neu'.($index + 1);
                $seen[$newRef] = true;
                $out[] = ['op' => 'add', 'ref' => $newRef, 'section' => $op['section']];

                continue;
            }
            if (! $byRef->has($ref) || isset($seen[$ref]) || ($scope === 'section' && $ref !== $target)) {
                continue;
            }
            $seen[$ref] = true;
            if ($byRef->get($ref)['locked']) {
                $out[] = ['op' => 'keep', 'ref' => $ref];

                continue;
            }
            if ($kind === 'update' && ($invalid || ! is_array($op['section'] ?? null))) {
                $warnings[] = 'Überarbeitung von '.$ref.' verworfen: '.implode(' ', $errors[$index] ?? ['kein Abschnitt']);
                $out[] = ['op' => 'keep', 'ref' => $ref];

                continue;
            }
            $out[] = $kind === 'update'
                ? ['op' => 'update', 'ref' => $ref, 'section' => $op['section']]
                : ['op' => in_array($kind, ['keep', 'move', 'delete'], true) ? $kind : 'keep', 'ref' => $ref];
        }
        if ($scope === 'section') {
            return $out;
        }
        // Missing refs stay where they were: after the closest preceding section that is still there
        $order = array_map(fn ($c) => $c['ref'], $current);
        foreach ($order as $position => $ref) {
            if (isset($seen[$ref])) {
                continue;
            }
            $at = 0;
            for ($p = $position - 1; $p >= 0; $p--) {
                $found = array_search($order[$p], array_column($out, 'ref'), true);
                if ($found !== false) {
                    $at = $found + 1;
                    break;
                }
            }
            array_splice($out, $at, 0, [['op' => 'keep', 'ref' => $ref]]);
            $seen[$ref] = true;
            $warnings[] = 'Abschnitt '.$ref.' fehlte in der Antwort und bleibt unverändert.';
        }

        return $out;
    }

    /**
     * @return string[]
     */
    protected function reviseRules(string $scope, ?string $target): array
    {
        $rules = [
            '- Überarbeiten: Du bekommst bestehende Abschnitte (je mit "ref", in Seitenreihenfolge) und einen Änderungswunsch. Gib "operations" in der neuen Reihenfolge der Seite aus.',
            '- {"op": "update", "ref": …, "section": {…}}: Der Abschnitt wird überarbeitet. "section" ist der vollständige neue Abschnitt nach dem Schema (alle Felder, Listen und Medien, nicht nur die geänderten). Behalte Block, Aufbau und alle Inhalte, die der Wunsch nicht betrifft, möglichst wörtlich bei.',
            '- Bilder: Soll ein Bild bleiben, übernimm Suchbegriff und Alt-Text unverändert. Nur für ein anderes Motiv einen neuen Suchbegriff.',
            '- Text in eckigen Klammern wie [Preis pro Monat] ist ein offener Platzhalter: schreibe ihn als [[PLATZHALTER: Preis pro Monat]]. Preise und Zahlen aus dem bestehenden Inhalt darfst du übernehmen, neue nicht erfinden.',
        ];
        if ($scope === 'section') {
            $rules[] = '- Überarbeite genau den Abschnitt "'.$target.'": gib genau eine Operation {"op": "update", "ref": "'.$target.'", "section": …} aus.';
            $rules[] = '- Lehnst du den Wunsch ab, gib trotzdem den unveränderten Abschnitt als update aus und begründe in "refusal".';

            return $rules;
        }

        return array_merge($rules, [
            '- {"op": "keep", "ref": …}: unverändert an dieser Stelle. {"op": "move", "ref": …}: unverändert, aber an neuer Stelle. {"op": "delete", "ref": …}: entfällt. {"op": "add", "ref": "neu1", "section": {…}}: neuer Abschnitt an dieser Stelle (refs "neu1", "neu2" …).',
            '- Jede bestehende ref kommt genau einmal vor. Abschnitte mit "gesperrt": true (gesperrt oder zentral gepflegte Vorlage) sind immer "keep" und behalten ihre Reihenfolge untereinander.',
            '- Ändere nur, was der Wunsch verlangt; Abschnitte ohne Bezug zum Wunsch bleiben "keep".',
            '- Lehnst du den Wunsch ab, gib für jede ref "keep" aus und begründe in "refusal".',
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $current
     * @param  array<int, array{title: string, url: string}>  $linkTargets
     */
    protected function reviseUserPrompt(string $instruction, array $current, string $scope, ?string $target, array $context, int $maxNew, array $linkTargets): string
    {
        $lines = ['Änderungswunsch: '.$instruction];
        if (! empty($context['page_title'])) {
            $lines[] = 'Seite: '.$context['page_title'];
        }
        if ($scope === 'section' && ! empty($context['page_outline']) && is_array($context['page_outline'])) {
            $lines[] = 'Übrige Abschnitte der Seite (nur zur Orientierung): '.implode(' | ', array_slice(array_map('strval', $context['page_outline']), 0, 20));
        }
        $lines[] = $scope === 'section' ? 'Abschnitt zum Überarbeiten:' : 'Bestehende Abschnitte in Seitenreihenfolge (ein JSON-Objekt je Zeile):';
        foreach ($current as $section) {
            if ($scope === 'section' && $section['ref'] !== $target) {
                continue;
            }
            $line = $section['locked']
                ? ['ref' => $section['ref'], 'gesperrt' => true, 'beschreibung' => $section['label']]
                : array_filter([
                    'ref'          => $section['ref'],
                    'block'        => $section['block'],
                    'beschreibung' => $section['label'] ?: null,
                    'fields'       => (object) $section['fields'],
                    'lists'        => (object) $section['lists'],
                    'media'        => $section['media'],
                ], fn ($v) => $v !== null);
            $lines[] = json_encode($line, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        if ($scope === 'page') {
            $lines[] = $maxNew > 0 ? 'Höchstens '.$maxNew.' neue Abschnitte (op "add").' : 'Keine neuen Abschnitte.';
        }
        $lines[] = $linkTargets === []
            ? 'Vorhandene Seiten: keine bekannt, neue Links daher "#".'
            : "Vorhandene Seiten (Titel → Adresse):\n".collect($linkTargets)->map(fn ($t) => '- '.$t['title'].' → '.$t['url'])->implode("\n");

        return implode("\n", $lines);
    }

    /**
     * One strict json_schema call plus repair rounds: the validator's error list goes back to the model until the
     * output is valid or the rounds are used up. A refusal of the model ends the loop without repair.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<int, array{role: string, content: string}>  $messages
     * @param  callable(mixed): array<int, string[]>  $validate
     * @return array{output: mixed, errors: array<int, string[]>, round: int, first_attempt_valid: bool, usage: array{prompt_tokens: int, completion_tokens: int}, model: string, refusal: ?string}
     */
    protected function complete(array $payload, array $messages, callable $validate): array
    {
        $maxRounds = (int) config('ai.builder.max_repair_rounds', 2);
        $usage = ['prompt_tokens' => 0, 'completion_tokens' => 0];
        $round = 0;
        $output = null;
        $errors = [];
        $firstAttemptValid = false;
        $usedModel = (string) $payload['model'];
        $refusal = null;

        while (true) {
            $result = $this->client->chat($payload + ['messages' => $messages]);
            $usage['prompt_tokens'] += (int) ($result['usage']['prompt_tokens'] ?? 0);
            $usage['completion_tokens'] += (int) ($result['usage']['completion_tokens'] ?? 0);
            $usedModel = $result['model'] ?: $usedModel;

            $output = json_decode($result['text'], true);
            // The model declined (third parties, insults): no repair round, no draft
            if (is_array($output) && is_string($output['refusal'] ?? null) && trim($output['refusal']) !== '') {
                $refusal = trim($output['refusal']);
                break;
            }
            $errors = json_last_error() === JSON_ERROR_NONE
                ? $validate($output)
                : [-1 => ['Die Antwort ist kein gültiges JSON'.($result['finish_reason'] === 'length' ? ' (abgeschnitten, bitte kürzer)' : '').'.']];

            if ($round === 0) {
                $firstAttemptValid = $errors === [];
            }
            if ($errors === [] || $round >= $maxRounds) {
                break;
            }

            $round++;
            $messages[] = ['role' => 'assistant', 'content' => $result['text']];
            $messages[] = ['role' => 'user', 'content' => "Deine Antwort hat Fehler. Korrigiere sie und gib das vollständige JSON erneut aus:\n- "
                .implode("\n- ", array_merge(...array_values($errors)))];
        }

        return [
            'output'              => $output,
            'errors'              => $errors,
            'round'               => $round,
            'first_attempt_valid' => $firstAttemptValid,
            'usage'               => $usage,
            'model'               => $usedModel,
            'refusal'             => $refusal,
        ];
    }

    protected function systemPrompt(array $manifest, ?string $tonePrompt, bool $content = false, array $extraRules = []): string
    {
        $catalog = collect($manifest['blocks'] ?? [])->map(function (array $block) {
            $line = '- '.$block['id'].': '.($block['name'] ?? $block['id']);
            if (! empty($block['description'])) {
                $line .= ' – '.$block['description'];
            }
            $fields = collect($block['fields'] ?? [])->map(fn ($f) => $this->describeField($f))->implode(', ');
            if ($fields !== '') {
                $line .= "\n  Felder: ".$fields;
            }
            foreach ($block['lists'] ?? [] as $list) {
                $line .= "\n  Liste ".$list['key'].(isset($list['label']) ? ' ('.$list['label'].')' : '')
                    .' mit '.($list['min'] ?? 0).'–'.($list['max'] ?? 12).' Einträgen: '
                    .collect($list['fields'] ?? [])->map(fn ($f) => $this->describeField($f))->implode(', ');
            }
            if (! empty($block['media'])) {
                $line .= "\n  Medien: ".collect($block['media'])->pluck('slot')->implode(', ');
            }

            return $line;
        })->implode("\n");

        return implode("\n", array_filter([
            'Du erstellst Abschnitte für eine Webseite in einem Page Builder. Antworte nur mit JSON nach dem vorgegebenen Schema.',
            'Regeln:',
            '- Verwende ausschließlich Blöcke aus dem Katalog unten und wähle pro Abschnitt den passendsten.',
            '- Nennt der Auftrag einen Block, eine Vorlage oder einen Effekt, den der Katalog nicht hat, baue den Abschnitt mit den passendsten vorhandenen Blöcken und lehne nicht ab.',
            '- Schreibe auf Deutsch in der Sie-Form, sachlich und konkret, ohne Superlative, ohne Garantieversprechen.',
            '- Schreibe nur über das Angebot des eigenen Unternehmens. Keine Aussagen über andere Unternehmen, Marken, Wettbewerber oder reale Personen, keine Vergleiche mit ihnen, keine Beleidigungen, Vorwürfe oder Abwertungen. Verlangt der Auftrag so etwas, gib "sections": [] aus und begründe in "refusal" in einem Satz, sonst ist "refusal" leer.',
            '- Erfinde keine Preise, Tarife, Prozente, Mengen, Fristen, Kontaktdaten oder Rechtstexte. Schreibe stattdessen [[PLATZHALTER: Beschreibung]] und liste jeden Platzhalter zusätzlich in "placeholders".',
            '- Platzhalter stehen genau an der Stelle des fehlenden Werts, und der Satz muss grammatisch stimmen, wenn dort später der echte Wert steht (z. B. "Sichern Sie sich [[PLATZHALTER: Dauer der Preisgarantie]] Preisgarantie." wird zu "Sichern Sie sich 24 Monate Preisgarantie."). Die Beschreibung nennt den Wert als kurze Wortgruppe (2–5 Wörter, z. B. "Dauer der Preisgarantie", "Arbeitspreis in Cent pro kWh") und wiederholt keine Wörter des Satzes. Platzhalter nie als Satzglied mit Artikel oder Verb missbrauchen; passt das nicht, formuliere den Satz um oder schreibe den Platzhalter als eigenen kurzen Satz.',
            '- Rich-Text-Felder nur mit <p>, <strong>, <em>, <ul>, <li>, <a>. Keine Klassen, kein CSS, keine Bilder im Text.',
            ...($content ? [
                '- Inhalte vorschlagen: Fülle JEDES Textfeld mit echtem, zum Auftrag passendem deutschem Text, auch in jedem Listeneintrag (jede Folie, jede Karte, jeder Eintrag bekommt eigene Dachzeile, Überschrift, Text und Button-Beschriftung, soweit die Felder es vorsehen). Keine Platzhalter wie "Lorem", "Titel", "Text hier" und keine leeren Felder, außer ein Feld passt inhaltlich nicht.',
                '- Rollen: overline = kurze Dachzeile (2–4 Wörter), headline = Überschrift, paragraph = ein bis drei Sätze, button = Handlungsaufforderung mit 1–4 Wörtern, caption = kurze Bildunterschrift.',
                '- Felder eines Blocks außerhalb der Listen (auch Schlüssel mit "child:", z. B. Dachzeile und Überschrift über Karten oder Folien) sind der Kopf des Abschnitts: Fülle sie mit Text zum Thema des Auftrags, nie mit allgemeinen Vorlagentexten.',
                '- Bilder: Gib je Bild-Feld und je Eintrag in "media" einen kurzen deutschen Suchbegriff (2–4 Substantive, das Hauptmotiv als erstes Wort, z. B. "Wärmepumpe Garten Haus") und einen sachlichen Alt-Text (was auf dem Bild zu sehen ist, ohne "Bild von"). Die Bilder werden in der Mediathek des Mandanten gesucht.',
                '- Link-Felder: nur Adressen aus der Liste "Vorhandene Seiten" im Auftrag oder Adressen, die der Nutzer selbst nennt. Passt keine Seite, schreibe "#". Erfinde keine Adressen.',
                '- Fakten, die geprüft werden müssen (Preise, Tarife, Termine, Fristen, Kennzahlen, Namen, Zitate realer Personen), nicht erfinden: [[PLATZHALTER: …]] und in "placeholders" aufführen.',
            ] : [
                '- Link-Felder leer lassen, außer der Nutzer nennt eine Adresse.',
                '- Bilder werden nicht erzeugt: gib unter "media" je Bild-Slot einen kurzen deutschen Suchbegriff für die Mediathek an.',
            ]),
            ...$extraRules,
            '- Halte die Zeichenlimits ein, Listen haben genau die geforderte Anzahl Einträge innerhalb der Grenzen.',
            $tonePrompt ? "Tonalität des Mandanten:\n".$tonePrompt : null,
            "Katalog:\n".$catalog,
        ]));
    }

    protected function describeField(array $field): string
    {
        $parts = [($field['type'] ?? 'text') === 'image' ? 'Bild {query, alt}' : ($field['type'] ?? 'text')];
        if (! empty($field['role'])) {
            $parts[] = 'Rolle '.$field['role'];
        }
        if (isset($field['maxLength'])) {
            $parts[] = 'max '.$field['maxLength'];
        }
        if (! empty($field['required'])) {
            $parts[] = 'Pflicht';
        }
        if (($field['type'] ?? null) === 'enum' && ! empty($field['options'])) {
            $parts[] = implode('|', $field['options']);
        }

        return $field['key'].(isset($field['label']) ? ' "'.$field['label'].'"' : '').' ('.implode(', ', $parts).')';
    }

    /**
     * @param  array<int, array{title: string, url: string}>|null  $linkTargets  null = content suggestions off
     */
    protected function userPrompt(string $prompt, array $context, int $maxSections, ?array $linkTargets = null): string
    {
        $lines = ['Auftrag: '.$prompt, 'Erzeuge höchstens '.$maxSections.' Abschnitt(e).'];
        if (! empty($context['page_title'])) {
            $lines[] = 'Seite: '.$context['page_title'];
        }
        if (! empty($context['page_outline']) && is_array($context['page_outline'])) {
            $lines[] = 'Vorhandene Abschnitte der Seite: '.implode(' | ', array_slice($context['page_outline'], 0, 20));
        }
        if (! empty($context['tone'])) {
            $lines[] = 'Gewünschter Ton: '.$context['tone'];
        }
        if ($linkTargets !== null) {
            $lines[] = $linkTargets === []
                ? 'Vorhandene Seiten: keine bekannt, Links daher "#".'
                : "Vorhandene Seiten (Titel → Adresse):\n".collect($linkTargets)->map(fn ($t) => '- '.$t['title'].' → '.$t['url'])->implode("\n");
        }

        return implode("\n", $lines);
    }

    /**
     * Drop anything the admin must not get: unknown keys, tags/attributes outside the allowlist.
     * With content suggestions: images resolved against the media library, links limited to known pages.
     */
    protected function normalize(array $section, array $manifest, ContentResolution $resolve, ?array $previous = null): array
    {
        $block = collect($manifest['blocks'])->firstWhere('id', $section['block']);
        // Revision of the same block: images whose search term stayed the same are kept as they are
        $before = $previous !== null && ($previous['block'] ?? null) === $section['block'] ? $previous : null;
        $clean = fn (array $fields, array $values, array $old = []) => collect($fields)->mapWithKeys(function ($field) use ($values, $resolve, $old) {
            $type = $field['type'] ?? 'text';
            $value = $values[$field['key']] ?? (in_array($type, SectionSchema::TEXT_TYPES, true) ? '' : null);
            if ($type === 'image') {
                if (! is_array($value)) {
                    return [$field['key'] => null];
                }

                return [$field['key'] => self::sameImage($value, $old[$field['key']] ?? null) ? self::keptImage($value) : $this->image($value, $resolve)];
            }
            if (is_string($value)) {
                $value = $type === 'rich' ? self::sanitizeRich($value) : self::plainText($value);
            }
            if ($type === 'link') {
                $value = $resolve->link((string) $value, ($field['role'] ?? null) === 'button', $field['key']);
            }

            return [$field['key'] => $value];
        })->all();

        $lists = [];
        foreach ($block['lists'] ?? [] as $list) {
            $oldItems = (array) ($before['lists'][$list['key']] ?? []);
            $lists[$list['key']] = array_map(
                fn ($item, $i) => $clean($list['fields'] ?? [], is_array($item) ? $item : [], is_array($oldItems[$i] ?? null) ? $oldItems[$i] : []),
                $section['lists'][$list['key']] ?? [],
                array_keys($section['lists'][$list['key']] ?? [])
            );
        }
        $oldMedia = collect((array) ($before['media'] ?? []))->filter(fn ($m) => is_array($m))->keyBy('slot');

        return [
            'block'  => $section['block'],
            'fields' => (object) $clean($block['fields'] ?? [], $section['fields'] ?? [], $before !== null ? (array) ($before['fields'] ?? []) : []),
            'lists'  => (object) $lists,
            'media'  => array_values(array_map(
                fn ($m) => $resolve->content
                    ? ['slot' => $m['slot']] + (self::sameImage($m, $oldMedia->get($m['slot'])) ? self::keptImage($m) : $this->image($m, $resolve))
                    : ['slot' => $m['slot'], 'query' => self::plainText((string) $m['query'])],
                $section['media'] ?? []
            )),
        ];
    }

    /**
     * Same image as before: the model repeated the search term of the existing image.
     */
    protected static function sameImage(array $value, mixed $old): bool
    {
        $query = mb_strtolower(trim((string) ($value['query'] ?? '')));

        return is_array($old) && $query !== '' && $query === mb_strtolower(trim((string) ($old['query'] ?? '')));
    }

    /**
     * @return array{query: string, alt: string, media: null, keep: true}
     */
    protected static function keptImage(array $value): array
    {
        return [
            'query' => self::plainText((string) ($value['query'] ?? '')),
            'alt'   => self::plainText((string) ($value['alt'] ?? '')),
            'media' => null,
            'keep'  => true,
        ];
    }

    /**
     * Search term + alt text → best unused hit of the tenant's media library (or none).
     *
     * @return array{query: string, alt: string, media: array<string, mixed>|null}
     */
    protected function image(array $value, ContentResolution $resolve): array
    {
        $query = self::plainText((string) ($value['query'] ?? ''));
        $alt = self::plainText((string) ($value['alt'] ?? ''));
        $media = null;
        if ($resolve->content && $resolve->clientId !== null && $query !== '') {
            foreach ($this->mediaSearch->images($resolve->clientId, $query) as $candidate) {
                $id = (int) ($candidate['id'] ?? 0);
                if ($id === 0 || isset($this->usedMedia[$id])) {
                    continue;
                }
                $this->usedMedia[$id] = true;
                $media = $candidate;
                break;
            }
            $media === null ? $resolve->mediaMissing++ : $resolve->mediaFound++;
        }

        return ['query' => $query, 'alt' => $alt, 'media' => $media];
    }

    /**
     * @return array<int, array{title: string, url: string}>
     */
    protected function cleanLinkTargets(array $targets): array
    {
        return collect($targets)
            ->filter(fn ($t) => is_array($t) && is_string($t['url'] ?? null) && $t['url'] !== '')
            ->map(fn ($t) => ['title' => trim(strip_tags((string) ($t['title'] ?? ''))) ?: $t['url'], 'url' => trim($t['url'])])
            ->unique('url')
            ->take(80)
            ->values()
            ->all();
    }

    /**
     * Fields and lists that only reference other records (template uuid, form id …) are never
     * filled by the model: an invented id was resolved as a component template (K6). Blocks with
     * nothing left to fill are dropped from the catalog.
     *
     * @param  array<string, mixed>  $manifest
     * @return array<string, mixed>
     */
    public static function contentManifest(array $manifest): array
    {
        $isRef = fn (array $f) => (bool) preg_match('/(^|[_.])(uuid|id|ids)$/i', (string) ($f['key'] ?? ''));
        $blocks = [];
        foreach ($manifest['blocks'] ?? [] as $block) {
            $block['fields'] = array_values(array_filter($block['fields'] ?? [], fn ($f) => ! $isRef($f)));
            $block['lists'] = array_values(array_filter(array_map(function ($list) use ($isRef) {
                $list['fields'] = array_values(array_filter($list['fields'] ?? [], fn ($f) => ! $isRef($f)));

                return $list;
            }, $block['lists'] ?? []), fn ($list) => $list['fields'] !== []));
            if ($block['fields'] === [] && $block['lists'] === [] && ($block['media'] ?? []) === []) {
                continue;
            }
            $blocks[] = $block;
        }
        $manifest['blocks'] = $blocks;

        return $manifest;
    }

    /**
     * Placeholder descriptions as they appear in the texts, without a doubled "PLATZHALTER:".
     *
     * @return string[]
     */
    public static function placeholdersIn(mixed $value): array
    {
        // Only plain data: models, resources and collections can reach themselves through their relations
        if (is_object($value)) {
            $value = $value instanceof \stdClass ? (array) $value : [];
        }
        if (is_array($value)) {
            return array_values(array_unique(array_merge([], ...array_map(fn ($v) => self::placeholdersIn($v), array_values($value)))));
        }
        if (! is_string($value) || ! preg_match_all('/\[\[\s*([^\]]*?)\s*\]\]/u', $value, $m)) {
            return [];
        }

        return array_map(fn ($raw) => trim((string) preg_replace('/^(?:platzhalter\s*:\s*)+/iu', '', $raw)) ?: 'Platzhalter', $m[1]);
    }

    /**
     * Plain text field: no markup, no script/style content left behind as text, entities decoded (K35).
     */
    public static function plainText(string $value): string
    {
        $value = (string) preg_replace('#<(script|style)\b[^>]*>.*?</\1\s*>#is', '', $value);
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(strip_tags($value));
    }

    public static function sanitizeRich(string $html): string
    {
        $html = (string) preg_replace('#<(script|style)\b[^>]*>.*?</\1\s*>#is', '', $html);
        $html = strip_tags($html, '<p><strong><em><ul><ol><li><a><br>');
        $html = (string) preg_replace('/<(p|strong|em|ul|ol|li|br)\b[^>]*>/i', '<$1>', $html);

        return (string) preg_replace_callback('/<a\b[^>]*>/i', function (array $m) {
            if (preg_match('/href\s*=\s*"([^"]*)"/i', $m[0], $href)
                && preg_match('#^(https?://|/(?!/)|\#|mailto:|tel:)#i', $href[1])) {
                return '<a href="'.htmlspecialchars($href[1], ENT_QUOTES).'">';
            }

            return '<a>';
        }, $html);
    }
}
