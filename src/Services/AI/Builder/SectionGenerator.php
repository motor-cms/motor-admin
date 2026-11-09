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
    ): array {
        $model ??= (string) config('ai.mistral.models.builder');
        $maxRounds = (int) config('ai.builder.max_repair_rounds', 2);
        $validator = new SectionValidator($manifest, $maxSections, $content);
        $linkTargets = $this->cleanLinkTargets($linkTargets);
        $this->usedMedia = [];
        $started = hrtime(true);

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

        $usage = ['prompt_tokens' => 0, 'completion_tokens' => 0];
        $round = 0;
        $output = null;
        $errors = [];
        $firstAttemptValid = false;
        $usedModel = $model;

        while (true) {
            $result = $this->client->chat($payload + ['messages' => $messages]);
            $usage['prompt_tokens'] += (int) ($result['usage']['prompt_tokens'] ?? 0);
            $usage['completion_tokens'] += (int) ($result['usage']['completion_tokens'] ?? 0);
            $usedModel = $result['model'] ?: $model;

            $output = json_decode($result['text'], true);
            $errors = json_last_error() === JSON_ERROR_NONE
                ? $validator->validate($output)
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

        $warnings = $validator->warnings;
        $placeholders = is_array($output['placeholders'] ?? null) ? array_values(array_filter($output['placeholders'], 'is_string')) : [];
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
        $warnings = array_merge($warnings, $resolve->warnings);
        $placeholders = array_merge($placeholders, $resolve->placeholders);

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

    protected function systemPrompt(array $manifest, ?string $tonePrompt, bool $content = false): string
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
            '- Schreibe auf Deutsch in der Sie-Form, sachlich und konkret, ohne Superlative, ohne Garantieversprechen.',
            '- Erfinde keine Preise, Tarife, Prozente, Mengen, Fristen, Kontaktdaten oder Rechtstexte. Schreibe stattdessen [[PLATZHALTER: Beschreibung]] und liste jeden Platzhalter zusätzlich in "placeholders".',
            '- Rich-Text-Felder nur mit <p>, <strong>, <em>, <ul>, <li>, <a>. Keine Klassen, kein CSS, keine Bilder im Text.',
            ...($content ? [
                '- Inhalte vorschlagen: Fülle JEDES Textfeld mit echtem, zum Auftrag passendem deutschem Text, auch in jedem Listeneintrag (jede Folie, jede Karte, jeder Eintrag bekommt eigene Dachzeile, Überschrift, Text und Button-Beschriftung, soweit die Felder es vorsehen). Keine Platzhalter wie "Lorem", "Titel", "Text hier" und keine leeren Felder, außer ein Feld passt inhaltlich nicht.',
                '- Rollen: overline = kurze Dachzeile (2–4 Wörter), headline = Überschrift, paragraph = ein bis drei Sätze, button = Handlungsaufforderung mit 1–4 Wörtern, caption = kurze Bildunterschrift.',
                '- Bilder: Gib je Bild-Feld und je Eintrag in "media" einen kurzen deutschen Suchbegriff (2–4 Substantive, das Hauptmotiv als erstes Wort, z. B. "Wärmepumpe Garten Haus") und einen sachlichen Alt-Text (was auf dem Bild zu sehen ist, ohne "Bild von"). Die Bilder werden in der Mediathek des Mandanten gesucht.',
                '- Link-Felder: nur Adressen aus der Liste "Vorhandene Seiten" im Auftrag oder Adressen, die der Nutzer selbst nennt. Passt keine Seite, schreibe "#". Erfinde keine Adressen.',
                '- Fakten, die geprüft werden müssen (Preise, Tarife, Termine, Fristen, Kennzahlen, Namen, Zitate realer Personen), nicht erfinden: [[PLATZHALTER: …]] und in "placeholders" aufführen.',
            ] : [
                '- Link-Felder leer lassen, außer der Nutzer nennt eine Adresse.',
                '- Bilder werden nicht erzeugt: gib unter "media" je Bild-Slot einen kurzen deutschen Suchbegriff für die Mediathek an.',
            ]),
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
    protected function normalize(array $section, array $manifest, ContentResolution $resolve): array
    {
        $block = collect($manifest['blocks'])->firstWhere('id', $section['block']);
        $clean = fn (array $fields, array $values) => collect($fields)->mapWithKeys(function ($field) use ($values, $resolve) {
            $type = $field['type'] ?? 'text';
            $value = $values[$field['key']] ?? (in_array($type, SectionSchema::TEXT_TYPES, true) ? '' : null);
            if ($type === 'image') {
                return [$field['key'] => is_array($value) ? $this->image($value, $resolve) : null];
            }
            if (is_string($value)) {
                $value = $type === 'rich' ? self::sanitizeRich($value) : trim(strip_tags($value));
            }
            if ($type === 'link' && $resolve->content) {
                $value = $resolve->link((string) $value, ($field['role'] ?? null) === 'button', $field['key']);
            }

            return [$field['key'] => $value];
        })->all();

        $lists = [];
        foreach ($block['lists'] ?? [] as $list) {
            $lists[$list['key']] = array_map(
                fn ($item) => $clean($list['fields'] ?? [], is_array($item) ? $item : []),
                $section['lists'][$list['key']] ?? []
            );
        }

        return [
            'block'  => $section['block'],
            'fields' => (object) $clean($block['fields'] ?? [], $section['fields'] ?? []),
            'lists'  => (object) $lists,
            'media'  => array_values(array_map(
                fn ($m) => $resolve->content
                    ? ['slot' => $m['slot']] + $this->image($m, $resolve)
                    : ['slot' => $m['slot'], 'query' => trim(strip_tags($m['query']))],
                $section['media'] ?? []
            )),
        ];
    }

    /**
     * Search term + alt text → best unused hit of the tenant's media library (or none).
     *
     * @return array{query: string, alt: string, media: array<string, mixed>|null}
     */
    protected function image(array $value, ContentResolution $resolve): array
    {
        $query = trim(strip_tags((string) ($value['query'] ?? '')));
        $alt = trim(strip_tags((string) ($value['alt'] ?? '')));
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

    public static function sanitizeRich(string $html): string
    {
        $html = strip_tags($html, '<p><strong><em><ul><ol><li><a><br>');
        $html = (string) preg_replace('/<(p|strong|em|ul|ol|li|br)\b[^>]*>/i', '<$1>', $html);

        return (string) preg_replace_callback('/<a\b[^>]*>/i', function (array $m) {
            if (preg_match('/href\s*=\s*"([^"]*)"/i', $m[0], $href)
                && preg_match('#^(https?://|/|\#|mailto:)#i', $href[1])) {
                return '<a href="'.htmlspecialchars($href[1], ENT_QUOTES).'">';
            }

            return '<a>';
        }, $html);
    }
}
