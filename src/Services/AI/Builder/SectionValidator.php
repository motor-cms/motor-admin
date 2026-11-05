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
     * @param  array<string, mixed>  $manifest
     */
    public function __construct(array $manifest, protected int $maxSections = 6)
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
    protected function validateSection(mixed $section, int $index): array
    {
        $at = 'Abschnitt '.($index + 1);
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
            if ($type !== 'link' && preg_match(self::NUMBER_PATTERN, $this->withoutPlaceholders($plain), $match)) {
                $errors[] = $label.': konkrete Preise, Prozente oder Mengen ("'.$match[0].'") nicht erfinden, stattdessen [[PLATZHALTER: …]] verwenden.';
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

    protected function withoutPlaceholders(string $text): string
    {
        return (string) preg_replace('/\[\[PLATZHALTER:[^\]]*\]\]/u', '', $text);
    }
}
