<?php

namespace Motor\Admin\Services\AI\Images\Stock;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Motor\Admin\Services\AI\AIProviderException;

/**
 * Pixabay API (https://pixabay.com/api/docs/): Content License, free, also commercial. Duties: no permanent
 * hotlinking (images are downloaded to our server), search answers cached for 24 hours, Pixabay named with search
 * results. Credit is not required by the licence, we still store and offer it.
 */
class PixabayProvider extends HttpStockProvider
{
    public const LICENSE = 'Pixabay Content License';

    public const LICENSE_URL = 'https://pixabay.com/service/license-summary/';

    public function name(): string
    {
        return 'pixabay';
    }

    protected function downloadHosts(): array
    {
        return ['pixabay.com'];
    }

    public function search(string $query, int $limit = 10): array
    {
        $query = trim($query);
        if (! $this->enabled() || $query === '') {
            return [];
        }
        $perPage = min(max($limit, 3), 200);
        $hits = Cache::remember('ai-images:pixabay:'.sha1(mb_strtolower($query).'|'.$perPage), now()->addDay(), function () use ($query, $perPage) {
            try {
                $response = Http::acceptJson()->timeout(15)->get((string) $this->config('base_url'), [
                    'key'         => (string) $this->config('api_key'),
                    'q'           => mb_substr($query, 0, 100),
                    'lang'        => 'de',
                    'image_type'  => 'photo',
                    'orientation' => 'horizontal',
                    'safesearch'  => 'true',
                    'per_page'    => $perPage,
                ]);
            } catch (ConnectionException) {
                throw new AIProviderException('Pixabay is not reachable.');
            }
            if (! $response->successful()) {
                throw new AIProviderException('Pixabay returned HTTP '.$response->status().'.', $response->status());
            }

            return (array) $response->json('hits', []);
        });

        return collect($hits)
            ->filter(fn ($h) => is_array($h) && isset($h['id'], $h['largeImageURL']))
            ->map(fn (array $h) => new StockImage(
                provider: 'pixabay',
                id: (string) $h['id'],
                pageUrl: (string) ($h['pageURL'] ?? ''),
                previewUrl: (string) ($h['webformatURL'] ?? $h['previewURL'] ?? ''),
                downloadUrl: (string) $h['largeImageURL'],
                width: (int) ($h['imageWidth'] ?? 0),
                height: (int) ($h['imageHeight'] ?? 0),
                authorName: $h['user'] ?? null,
                authorUrl: isset($h['user'], $h['user_id']) ? 'https://pixabay.com/users/'.rawurlencode((string) $h['user']).'-'.(int) $h['user_id'].'/' : null,
                alt: (string) ($h['tags'] ?? ''),
                raw: $h,
            ))
            ->take($limit)
            ->values()
            ->all();
    }

    public function origin(StockImage $image): array
    {
        $raw = $image->raw;
        unset($raw['userImageURL']);

        return [
            'source_type'       => 'stock',
            'provider'          => 'pixabay',
            'source_url'        => $image->pageUrl,
            'source_id'         => $image->id,
            'author_name'       => $image->authorName,
            'author_url'        => $image->authorUrl,
            'license'           => self::LICENSE,
            'license_url'       => self::LICENSE_URL,
            'attribution_text'  => 'Bild: '.($image->authorName ?: 'Unbekannt').' auf Pixabay',
            'credit_required'   => false,
            'contains_people'   => null,
            'contains_brands'   => null,
            'provider_response' => $raw,
        ];
    }
}
