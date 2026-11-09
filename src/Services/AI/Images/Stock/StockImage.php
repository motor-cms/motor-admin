<?php

namespace Motor\Admin\Services\AI\Images\Stock;

/**
 * One search hit of a stock provider, enough to show a preview and to import it with its licence data.
 */
final class StockImage
{
    /**
     * @param  array<string, mixed>  $raw  provider answer for this image (kept as licence proof)
     */
    public function __construct(
        public readonly string $provider,
        public readonly string $id,
        public readonly string $pageUrl,
        public readonly string $previewUrl,
        public readonly string $downloadUrl,
        public readonly int $width,
        public readonly int $height,
        public readonly ?string $authorName,
        public readonly ?string $authorUrl,
        public readonly string $alt,
        public readonly array $raw = [],
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'provider'     => $this->provider,
            'id'           => $this->id,
            'page_url'     => $this->pageUrl,
            'preview_url'  => $this->previewUrl,
            'width'        => $this->width,
            'height'       => $this->height,
            'author_name'  => $this->authorName,
            'author_url'   => $this->authorUrl,
            'alt'          => $this->alt,
        ];
    }
}
