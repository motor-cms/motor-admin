<?php

namespace Motor\Admin\Services\AI\Images\Stock;

/**
 * Licence-free stock photos that may be copied into the media library (Pexels, Pixabay).
 * Unsplash is deliberately missing: its API guidelines require hotlinking and forbid re-hosting.
 */
interface StockImageProvider
{
    /** pexels | pixabay */
    public function name(): string;

    /** Only with an API key; without one the provider is invisible. */
    public function enabled(): bool;

    /**
     * @return list<StockImage>
     */
    public function search(string $query, int $limit = 10): array;

    /** Image bytes for the import (never hotlinked). */
    public function download(StockImage $image): string;

    /**
     * Origin for the media library (see MediaImporter): licence, credit line, author, source.
     *
     * @return array<string, mixed>
     */
    public function origin(StockImage $image): array;
}
