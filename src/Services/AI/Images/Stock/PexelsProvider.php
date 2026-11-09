<?php

namespace Motor\Admin\Services\AI\Images\Stock;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Motor\Admin\Services\AI\AIProviderException;

/**
 * Pexels API (https://www.pexels.com/api/documentation/): free, also commercial. Duties: a visible link to Pexels
 * and the photographer's name where possible. 200 requests/hour, 20,000/month.
 */
class PexelsProvider extends HttpStockProvider
{
    public const LICENSE = 'Pexels License';

    public const LICENSE_URL = 'https://www.pexels.com/license/';

    public function name(): string
    {
        return 'pexels';
    }

    protected function downloadHosts(): array
    {
        return ['images.pexels.com'];
    }

    public function search(string $query, int $limit = 10): array
    {
        if (! $this->enabled() || trim($query) === '') {
            return [];
        }
        try {
            $response = Http::withHeaders(['Authorization' => (string) $this->config('api_key')])
                ->baseUrl(rtrim((string) $this->config('base_url'), '/'))
                ->acceptJson()
                ->timeout(15)
                ->get('/search', ['query' => $query, 'per_page' => min(max($limit, 1), 40), 'locale' => 'de-DE', 'orientation' => 'landscape']);
        } catch (ConnectionException) {
            throw new AIProviderException('Pexels is not reachable.');
        }
        if (! $response->successful()) {
            throw new AIProviderException('Pexels returned HTTP '.$response->status().'.', $response->status());
        }

        return collect($response->json('photos', []))
            ->filter(fn ($p) => is_array($p) && isset($p['id'], $p['src']['original']))
            ->map(fn (array $p) => new StockImage(
                provider: 'pexels',
                id: (string) $p['id'],
                pageUrl: (string) ($p['url'] ?? ''),
                previewUrl: (string) ($p['src']['medium'] ?? $p['src']['original']),
                // large2x is 1880 px wide at most, enough for the web and much smaller than the original
                downloadUrl: (string) ($p['src']['large2x'] ?? $p['src']['original']),
                width: (int) ($p['width'] ?? 0),
                height: (int) ($p['height'] ?? 0),
                authorName: $p['photographer'] ?? null,
                authorUrl: $p['photographer_url'] ?? null,
                alt: (string) ($p['alt'] ?? ''),
                raw: $p,
            ))
            ->take($limit)
            ->values()
            ->all();
    }

    public function origin(StockImage $image): array
    {
        $author = $image->authorName ?: 'Unbekannt';

        return [
            'source_type'       => 'stock',
            'provider'          => 'pexels',
            'source_url'        => $image->pageUrl,
            'source_id'         => $image->id,
            'author_name'       => $image->authorName,
            'author_url'        => $image->authorUrl,
            'license'           => self::LICENSE,
            'license_url'       => self::LICENSE_URL,
            'attribution_text'  => 'Foto: '.$author.' auf Pexels',
            'credit_required'   => true,
            'contains_people'   => null,
            'contains_brands'   => null,
            'provider_response' => $image->raw,
        ];
    }
}
