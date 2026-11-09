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

    /** @var array<string, true> addresses the editor wrote into the prompt */
    protected array $promptUrls = [];

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
        // Whole addresses only: a substring check let "/" or "javascript:" through as soon as the prompt contained it (K31)
        preg_match_all('#(?:https?://[^\s"\'<>]+|mailto:[^\s"\'<>]+|tel:[+\d][\d\s/-]*\d|(?<![\w/.:])/[a-z0-9][\w\-/]*)#iu', $prompt, $m);
        foreach ($m[0] as $url) {
            $this->promptUrls[rtrim($url, '.,;:!?)')] = true;
        }
    }

    /**
     * Only web, root-relative, anchor, mail and phone addresses; everything else (javascript:, data:, //host) is unsafe.
     */
    public static function safeScheme(string $url): bool
    {
        return (bool) preg_match('#^(https?://|/(?!/)|\#|mailto:|tel:)#i', $url);
    }

    /**
     * Only known pages, addresses from the prompt, anchors or "#". Anything else
     * the model invented becomes "#" (the health check flags it) plus a warning.
     */
    public function link(string $url, bool $isButton, string $key): string
    {
        $url = trim($url);
        if (! $this->content) {
            // Structure only: no page list, but never an unsafe scheme
            if ($url === '' || self::safeScheme($url)) {
                return $url;
            }
            $this->warnings[] = 'Link "'.mb_substr($url, 0, 80).'" ('.$key.') hat ein unsicheres Schema und wurde durch "#" ersetzt.';

            return '#';
        }
        if ($url === '') {
            return $isButton ? '#' : '';
        }
        if ($url === '#' || isset($this->allowedUrls[$url]) || isset($this->promptUrls[$url])) {
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
