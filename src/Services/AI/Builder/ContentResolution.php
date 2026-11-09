<?php

namespace Motor\Admin\Services\AI\Builder;

/**
 * State of one section request while its output is normalized with content
 * suggestions: which links are allowed, what was found in the media library,
 * and what the editor still has to check (warnings, placeholders).
 */
class ContentResolution
{
    /** @var string[] */
    public array $warnings = [];

    /** @var string[] */
    public array $placeholders = [];

    public int $mediaFound = 0;

    public int $mediaMissing = 0;

    /** @var array<string, true> */
    protected array $allowedUrls = [];

    /**
     * @param  array<int, array{title: string, url: string}>  $linkTargets
     */
    public function __construct(
        public bool $content,
        array $linkTargets,
        protected string $prompt,
        public ?int $clientId,
    ) {
        foreach ($linkTargets as $target) {
            $this->allowedUrls[$target['url']] = true;
        }
    }

    /**
     * Only known pages, addresses from the prompt, anchors or "#". Anything else
     * the model invented becomes "#" (the health check flags it) plus a warning.
     */
    public function link(string $url, bool $isButton, string $key): string
    {
        $url = trim($url);
        if ($url === '') {
            return $isButton ? '#' : '';
        }
        if ($url === '#' || isset($this->allowedUrls[$url]) || str_contains($this->prompt, $url)) {
            return $url;
        }
        // Known page with a trailing slash or without the leading one
        $normalized = '/'.trim($url, '/');
        if (isset($this->allowedUrls[$normalized])) {
            return $normalized;
        }
        $this->warnings[] = 'Link "'.$url.'" ('.$key.') ist keine vorhandene Seite und wurde durch "#" ersetzt.';
        $this->placeholders[] = 'Linkziel prüfen';

        return '#';
    }
}
