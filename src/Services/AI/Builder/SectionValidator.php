<?php

namespace Motor\Admin\Services\AI\Builder;

/**
 * Validates the model's intermediate JSON against the catalog manifest.
 *
 * Strict json_schema is not a guarantee (seen in the spike: invalid JSON from
 * mistral-large-2512, lengths over the limit), so the server checks again and
 * feeds the error list back into a repair round. Error texts are German because
 * they go straight back to the model.
 */
class SectionValidator
{
    /**
     * Invented prices, rates and percentages: must be a [[PLATZHALTER: …]] instead.
     */
    protected const NUMBER_PATTERN = '/\d+(?:[.,]\d+)?\s?(?:€|EUR\b|Euro\b|ct\b|Cent\b|%|Prozent\b|kWh\b)/iu';

    protected const INFORMAL_PATTERN = '/\b(?:du|dich|dir|dein|deine|deinen|deinem|deiner|euch|euer)\b/u';

    /** @var array<string, array<string, mixed>> */
    protected array $blocks = [];

    /** @var string[] */
    public array $warnings = [];

    /**
     * Numbers with units that already stand in the page (revision context): the model may keep them,
     * only new ones must be placeholders.
     *
     * @var array<string, true>
     */
    protected array $knownNumbers = [];

    /**
     * @param  array<string, mixed>  $manifest
     */
    public function __construct(array $manifest, protected int $maxSections = 6, protected bool $content = false)
    {
        foreach ($manifest['blocks'] ?? [] as $block) {
            $this->blocks[(string) $block['id']] = $block;
        }
    }

    /**
     * @param  mixed  $output  decoded model output
     * @return array<int, string[]> errors per section index; key -1 = document level
     */
    public function validate(mixed $output): array
    {
        $this->warnings = [];
        $errors = [];

        if (! is_array($output) || ! isset($output['sections']) || ! is_array($output['sections'])) {
            return [-1 => ['Die Antwort muss ein Objekt mit "sections" (Liste) sein.']];
        }
        if ($output['sections'] === []) {
            $errors[-1][] = 'Mindestens ein Abschnitt ist nötig.';
        }
        if (count($output['sections']) > $this->maxSections) {
            $errors[-1][] = 'Höchstens '.$this->maxSections.' Abschnitte.';
        }
        if (isset($output['placeholders']) && ! is_array($output['placeholders'])) {
            $errors[-1][] = '"placeholders" muss eine Liste von Texten sein.';
        }

        foreach (array_values($output['sections']) as $index => $section) {
            $sectionErrors = $this->validateSection($section, $index);
            if ($sectionErrors !== []) {
                $errors[$index] = $sectionErrors;
            }
        }

        return $errors;
    }

    /**
     * @return string[]
     */
    protected function validateSection(mixed $section, int $index, ?string $at = null): array
    {
        $at ??= 'Abschnitt '.($index + 1);
        if (! is_array($section)) {
            return [$at.': muss ein Objekt sein.'];
        }

        $blockId = $section['block'] ?? null;
        if (! is_string($blockId) || ! isset($this->blocks[$blockId])) {
            return [$at.': unbekannter Block "'.(is_scalar($blockId) ? $blockId : '?').'", erlaubt sind nur Blöcke aus dem Katalog.'];
        }
        $block = $this->blocks[$blockId];
        $at .= ' ('.$blockId.')';
        $errors = [];

        $errors = array_merge($errors, $this->validateFields($block['fields'] ?? [], $section['fields'] ?? [], $at));

        $lists = $section['lists'] ?? [];
        if (! is_array($lists)) {
            $errors[] = $at.': "lists" muss ein Objekt sein.';
            $lists = [];
        }
        $knownLists = [];
        foreach ($block['lists'] ?? [] as $list) {
            $knownLists[$list['key']] = true;
            $items = $lists[$list['key']] ?? [];
            if (! is_array($items) || ! array_is_list($items)) {
                $errors[] = $at.': Liste "'.$list['key'].'" muss ein Array sein.';

                continue;
            }
            $min = (int) ($list['min'] ?? 0);
            $max = (int) ($list['max'] ?? 12);
            if (count($items) < $min || count($items) > $max) {
                $errors[] = $at.': Liste "'.$list['key'].'" braucht '.$min.' bis '.$max.' Einträge, hat '.count($items).'.';
            }
            foreach ($items as $itemIndex => $item) {
                $errors = array_merge($errors, $this->validateFields(
                    $list['fields'] ?? [],
                    $item,
                    $at.' '.$list['key'].'['.($itemIndex + 1).']'
                ));
            }
        }
        foreach (array_keys($lists) as $key) {
            if (! isset($knownLists[$key])) {
                $errors[] = $at.': unbekannte Liste "'.$key.'".';
            }
        }

        $slots = array_map(fn ($m) => (string) $m['slot'], $block['media'] ?? []);
        foreach ((array) ($section['media'] ?? []) as $media) {
            $slot = is_array($media) ? ($media['slot'] ?? null) : null;
            if (! in_array($slot, $slots, true)) {
                $errors[] = $at.': unbekannter Medien-Slot "'.(is_scalar($slot) ? $slot : '?').'".';
            } elseif (! is_string($media['query'] ?? null) || mb_strlen($media['query']) > SectionSchema::MEDIA_QUERY_MAX) {
                $errors[] = $at.': Medien-Suchbegriff für "'.$slot.'" fehlt oder ist zu lang.';
            } elseif ($this->content && (! is_string($media['alt'] ?? null) || mb_strlen($media['alt']) > SectionSchema::ALT_MAX)) {
                $errors[] = $at.': Alt-Text für "'.$slot.'" fehlt oder ist länger als '.SectionSchema::ALT_MAX.' Zeichen.';
            }
        }

        return $errors;
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @return string[]
     */
    protected function validateFields(array $fields, mixed $values, string $at): array
    {
        if (! is_array($values)) {
            return [$at.': "fields" muss ein Objekt sein.'];
        }

        $errors = [];
        $known = [];
        foreach ($fields as $field) {
            $key = (string) $field['key'];
            $known[$key] = true;
            $value = $values[$key] ?? null;
            $label = $at.' Feld "'.$key.'"';
            $type = $field['type'] ?? 'text';

            if ($value === null || $value === '') {
                if (! empty($field['required'])) {
                    $errors[] = $label.': Pflichtfeld fehlt.';
                }

                continue;
            }

            if ($type === 'image') {
                $query = is_array($value) ? ($value['query'] ?? null) : null;
                $alt = is_array($value) ? ($value['alt'] ?? '') : null;
                if (! is_string($query) || ! is_string($alt)) {
                    $errors[] = $label.': Bild braucht ein Objekt {"query": Suchbegriff, "alt": Alt-Text}.';
                } elseif (mb_strlen($query) > SectionSchema::MEDIA_QUERY_MAX || mb_strlen($alt) > SectionSchema::ALT_MAX) {
                    $errors[] = $label.': Suchbegriff höchstens '.SectionSchema::MEDIA_QUERY_MAX.', Alt-Text höchstens '.SectionSchema::ALT_MAX.' Zeichen.';
                } elseif (! empty($field['required']) && trim($query) === '') {
                    $errors[] = $label.': Suchbegriff fürs Bild fehlt.';
                }

                continue;
            }

            if ($type === 'boolean') {
                if (! is_bool($value)) {
                    $errors[] = $label.': muss true oder false sein.';
                }

                continue;
            }
            if ($type === 'number') {
                if (! is_int($value) && ! is_float($value)) {
                    $errors[] = $label.': muss eine Zahl sein.';
                }

                continue;
            }
            if (! is_string($value)) {
                $errors[] = $label.': muss Text sein.';

                continue;
            }
            if ($type === 'enum' && ($field['options'] ?? []) !== [] && ! in_array($value, array_map('strval', $field['options']), true)) {
                $errors[] = $label.': erlaubt sind nur '.implode(', ', $field['options']).'.';

                continue;
            }

            $plain = $type === 'rich' ? trim(strip_tags($value)) : $value;
            if (isset($field['maxLength']) && mb_strlen($plain) > (int) $field['maxLength']) {
                $errors[] = $label.': höchstens '.$field['maxLength'].' Zeichen, hat '.mb_strlen($plain).'.';
            }
            if ($type !== 'link' && ($invented = $this->inventedNumber($plain)) !== null) {
                $errors[] = $label.': konkrete Preise, Prozente oder Mengen ("'.$invented.'") nicht erfinden, stattdessen [[PLATZHALTER: …]] verwenden.';
            }
            if (preg_match(self::INFORMAL_PATTERN, mb_strtolower($this->withoutPlaceholders($plain)))) {
                $this->warnings[] = $label.': Du-Form gefunden, gewünscht ist die Sie-Form.';
            }
        }

        foreach (array_keys($values) as $key) {
            if (! isset($known[$key])) {
                $errors[] = $at.': unbekanntes Feld "'.$key.'".';
            }
        }

        return $errors;
    }

    /**
     * First price/percentage/amount in the text that is neither inside a placeholder nor already on the page.
     */
    protected function inventedNumber(string $plain): ?string
    {
        if (! preg_match_all(self::NUMBER_PATTERN, $this->withoutPlaceholders($plain), $matches)) {
            return null;
        }
        foreach ($matches[0] as $match) {
            if (! isset($this->knownNumbers[self::numberKey($match)])) {
                return $match;
            }
        }

        return null;
    }

    protected static function numberKey(string $match): string
    {
        return mb_strtolower((string) preg_replace('/\s+/u', '', $match));
    }

    /**
     * Numbers already in the existing sections (any string in the context, recursively).
     */
    public function rememberNumbers(mixed $value): void
    {
        if (is_string($value)) {
            if (preg_match_all(self::NUMBER_PATTERN, strip_tags($value), $matches)) {
                foreach ($matches[0] as $match) {
                    $this->knownNumbers[self::numberKey($match)] = true;
                }
            }

            return;
        }
        if (is_array($value)) {
            foreach ($value as $item) {
                $this->rememberNumbers($item);
            }
        }
    }

    /**
     * Revision output: operations in the new page order (see SectionSchema::forRevision).
     *
     * @param  array<int, array<string, mixed>>  $current  context sections: ref, block, locked
     * @return array<int, string[]> errors per operation index; key -1 = document level
     */
    public function validateRevision(mixed $output, array $current, string $scope, ?string $target, int $maxNew): array
    {
        $this->warnings = [];
        if (! is_array($output) || ! isset($output['operations']) || ! is_array($output['operations']) || ! array_is_list($output['operations'])) {
            return [-1 => ['Die Antwort muss ein Objekt mit "operations" (Liste) sein.']];
        }
        $byRef = [];
        foreach ($current as $section) {
            $byRef[(string) $section['ref']] = $section;
        }
        $errors = [];
        $seen = [];
        $added = 0;
        $locked = [];
        $ops = $output['operations'];

        if ($scope === 'section') {
            if (count($ops) !== 1 || ($ops[0]['op'] ?? null) !== 'update' || ($ops[0]['ref'] ?? null) !== $target) {
                $errors[-1][] = 'Gib genau eine Operation {"op": "update", "ref": "'.$target.'", "section": …} aus.';
            }
        }

        foreach ($ops as $index => $op) {
            $at = 'Operation '.($index + 1);
            if (! is_array($op)) {
                $errors[$index][] = $at.': muss ein Objekt sein.';

                continue;
            }
            $kind = $op['op'] ?? null;
            $ref = is_scalar($op['ref'] ?? null) ? (string) $op['ref'] : '';
            if (! in_array($kind, SectionSchema::REVISION_OPS, true)) {
                $errors[$index][] = $at.': "op" muss keep, move, update, delete oder add sein.';

                continue;
            }
            $at .= ' ('.$kind.' '.$ref.')';
            if ($kind === 'add') {
                if ($scope === 'section') {
                    $errors[$index][] = $at.': beim Überarbeiten eines Abschnitts keine neuen Abschnitte.';

                    continue;
                }
                if (isset($byRef[$ref]) || isset($seen[$ref])) {
                    $errors[$index][] = $at.': neue Abschnitte brauchen eine eigene ref wie "neu1", nicht eine vorhandene.';
                }
                $seen[$ref] = true;
                if (++$added > $maxNew) {
                    $errors[$index][] = $at.': höchstens '.$maxNew.' neue Abschnitte.';
                }
                $sectionErrors = $this->validateSection($op['section'] ?? null, $index, $at);
                if ($sectionErrors !== []) {
                    $errors[$index] = array_merge($errors[$index] ?? [], $sectionErrors);
                }

                continue;
            }
            if (! isset($byRef[$ref])) {
                $errors[$index][] = $at.': unbekannte ref "'.$ref.'", erlaubt sind nur die refs der bestehenden Abschnitte.';

                continue;
            }
            if (isset($seen[$ref])) {
                $errors[$index][] = $at.': ref "'.$ref.'" kommt mehrfach vor, jede ref genau einmal.';

                continue;
            }
            $seen[$ref] = true;
            $context = $byRef[$ref];
            // Locked: delete/update are no error (no repair round), the generator keeps the section and tells the editor
            if (! empty($context['locked'])) {
                $locked[] = $ref;

                continue;
            }
            if ($kind === 'update') {
                $sectionErrors = $this->validateSection($op['section'] ?? null, $index, $at);
                if ($sectionErrors !== []) {
                    $errors[$index] = array_merge($errors[$index] ?? [], $sectionErrors);
                }
            }
        }

        if ($scope === 'page') {
            $missing = array_values(array_diff(array_keys($byRef), array_keys($seen)));
            if ($missing !== []) {
                $errors[-1][] = 'Jede bestehende ref genau einmal angeben (keep, move, update oder delete), es fehlen: '.implode(', ', $missing).'.';
            }
            $lockedOrder = array_values(array_filter(array_keys($byRef), fn ($r) => ! empty($byRef[$r]['locked'])));
            if ($locked !== array_values(array_intersect($lockedOrder, $locked))) {
                $errors[-1][] = 'Gesperrte Abschnitte behalten ihre Reihenfolge: '.implode(', ', $lockedOrder).'.';
            }
            $remaining = array_filter($ops, fn ($op) => is_array($op) && ($op['op'] ?? null) !== 'delete');
            if ($remaining === []) {
                $errors[-1][] = 'Die Seite braucht mindestens einen Abschnitt.';
            }
        }

        return $errors;
    }

    protected function withoutPlaceholders(string $text): string
    {
        return (string) preg_replace('/\[\[PLATZHALTER:[^\]]*\]\]/u', '', $text);
    }
}
