<?php

namespace Motor\Admin\Services\AI\Builder;

/**
 * Guard rail for generated page copy (KI-Test K5): a page builder for a utility
 * must not publish statements about competitors or other real companies, and
 * nothing insulting or defamatory (UWG, üble Nachrede). The system prompt asks
 * the model to refuse; this class checks the prompt before the call and every
 * generated text afterwards, independent of what the model did.
 *
 * - Third parties: names from `ai.builder.third_party_names` (energy suppliers,
 *   comparison portals), minus the tenant's own names.
 * - Strong terms (insults, accusations of crime) are refused anywhere in the output.
 * - Weak terms ("Abzocke", "Betrug") are fine on their own (consumer advice
 *   about door-to-door scams is legitimate) but not next to a third-party name.
 * - Prompts that ask for insulting or defamatory copy are refused before the call.
 */
class ContentPolicy
{
    protected const STRONG = '/\b(?:abschaum|wie\s+dreck|verbrecher\w*|halsabschneider\w*|betrüger\w*|lügner\w*|gauner\w*|idiot\w*|vollidiot\w*|scheiß\w*|scheiss\w*|drecks\w*|mafia\w*)/iu';

    protected const WEAK = '/\b(?:abzock\w*|betrug\w*|kriminell\w*|wucher\w*|ausbeut\w*|lüge\w*|scam\w*|fraud\w*|rip-?off\w*|schlecht\w*|unseriös\w*|teuer\w*)/iu';

    protected const INTENT = '/\b(?:beleidig\w*|diffamier\w*|verleumd\w*|schlechtmach\w*|schlecht\s+mach\w*|herabsetz\w*|bloßstell\w*|lächerlich\s+mach\w*|hetz\w*|insult\w*|defam\w*)/iu';

    /** @var string[] */
    protected array $names;

    /**
     * @param  string[]  $thirdPartyNames
     * @param  string[]  $ownNames  tenant names, never treated as third parties
     */
    public function __construct(array $thirdPartyNames, array $ownNames = [])
    {
        $own = array_map(fn ($n) => mb_strtolower(trim((string) $n)), array_filter($ownNames));
        $this->names = array_values(array_filter(
            array_map(fn ($n) => trim((string) $n), $thirdPartyNames),
            fn ($n) => $n !== '' && ! in_array(mb_strtolower($n), $own, true),
        ));
    }

    /**
     * @return string|null reason when the prompt must be refused
     */
    public function checkPrompt(string $prompt): ?string
    {
        if (preg_match(self::INTENT, $prompt, $m)) {
            return 'prompt asks for disparaging copy ("'.$m[0].'")';
        }
        $name = $this->thirdParty($prompt);
        if ($name !== null && (preg_match(self::STRONG, $prompt) || preg_match(self::WEAK, $prompt))) {
            return 'prompt disparages third party "'.$name.'"';
        }

        return null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $sections  normalized sections
     * @return string|null reason when the output must be refused
     */
    public function checkSections(array $sections): ?string
    {
        $text = implode("\n", $this->texts($sections));
        if (preg_match(self::STRONG, $text, $m)) {
            return 'output contains insult "'.$m[0].'"';
        }
        $name = $this->thirdParty($text);
        if ($name !== null) {
            return 'output names third party "'.$name.'"';
        }

        return null;
    }

    protected function thirdParty(string $text): ?string
    {
        foreach ($this->names as $name) {
            $pattern = '/(?<![\pL\pN])'.preg_quote($name, '/').'(?![\pL\pN])/iu';
            if (preg_match($pattern, $text)) {
                return $name;
            }
        }

        return null;
    }

    /**
     * @return string[]
     */
    protected function texts(mixed $value): array
    {
        if (is_string($value)) {
            return [html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8')];
        }
        if (is_object($value)) {
            $value = (array) $value;
        }
        if (! is_array($value)) {
            return [];
        }
        $out = [];
        foreach ($value as $key => $item) {
            // search terms and alt texts are text too; block ids, slot names and media library hits are not
            if ($key === 'block' || $key === 'slot' || ($key === 'media' && (! is_array($item) || ! array_is_list($item)))) {
                continue;
            }
            array_push($out, ...$this->texts($item));
        }

        return $out;
    }
}
