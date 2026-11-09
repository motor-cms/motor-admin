<?php

namespace Motor\Admin\Services\AI\Builder;

/**
 * Finds images in the tenant's media library for a search term the model wrote
 * (plan: `search_media`). Returns candidates best first, as the admin's
 * FileResource shape, so the builder can set them like a pick from the media
 * library.
 */
interface MediaSearch
{
    /**
     * @return array<int, array<string, mixed>> FileResource arrays, best match first, images only
     */
    public function images(int $clientId, string $query, int $limit = 5): array;
}
