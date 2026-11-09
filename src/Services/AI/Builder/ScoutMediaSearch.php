<?php

namespace Motor\Admin\Services\AI\Builder;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Motor\Media\Http\Resources\FileResource;
use Motor\Media\Models\File;
use Throwable;

/**
 * Scout search over the files of a tenant (index motor_media_files_index:
 * description, alt_text, tags, file name), images only. Without the media
 * package or when the index fails, a plain LIKE over description and alt_text
 * takes over, so a missing Meilisearch never breaks the section request.
 *
 * Meilisearch matches loosely (any word, typo tolerance), which in the eval put
 * a photo about electricity costs on a slide about robots. A hit therefore has
 * to carry at least half of the search words (word start, so "Hand" does not
 * match "anhand") in alt text, description, tags or file name; otherwise the
 * slot keeps its placeholder, which is better than a wrong picture.
 */
class ScoutMediaSearch implements MediaSearch
{
    public function images(int $clientId, string $query, int $limit = 5): array
    {
        $query = trim($query);
        if ($query === '' || ! class_exists(File::class)) {
            return [];
        }

        try {
            $files = File::search($query)->where('client_id', $clientId)->take($limit * 4)->get();
        } catch (Throwable $e) {
            Log::warning('AI media search: scout failed, falling back to LIKE', ['error' => $e->getMessage()]);
            $files = $this->like($clientId, $query, $limit * 4);
        }
        if ($files->isEmpty()) {
            $files = $this->like($clientId, $query, $limit * 4);
        }

        $words = self::words($query);

        return $files
            ->filter(fn (File $file) => str_starts_with((string) $file->getFirstMedia('file')?->mime_type, 'image/'))
            ->map(fn (File $file) => [$file, self::relevance($words, self::haystack($file))])
            ->filter(fn (array $hit) => $hit[1] >= self::required($words))
            ->sortByDesc(fn (array $hit) => $hit[1])
            ->map(fn (array $hit) => $hit[0])
            ->take($limit)
            ->map(fn (File $file) => (new FileResource($file))->resolve(request()))
            ->values()
            ->all();
    }

    /**
     * Any word of the term (longer than 3 chars) in description or alt text.
     *
     * @return Collection<int, File>
     */
    protected function like(int $clientId, string $query, int $limit): Collection
    {
        $words = self::words($query);
        if ($words === []) {
            return collect();
        }

        return File::query()
            ->where('client_id', $clientId)
            ->where(function ($q) use ($words) {
                foreach ($words as $word) {
                    $q->orWhereRaw('LOWER(description) LIKE ?', ['%'.$word.'%'])
                        ->orWhereRaw('LOWER(alt_text) LIKE ?', ['%'.$word.'%']);
                }
            })
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Search words longer than 3 characters, lower case.
     *
     * @return list<string>
     */
    public static function words(string $query): array
    {
        return array_values(array_unique(array_filter(
            preg_split('/[^\pL\pN]+/u', mb_strtolower($query)) ?: [],
            fn ($w) => mb_strlen($w) > 3
        )));
    }

    /** Half of the words, at least one (a query of only short words needs none). */
    public static function required(array $words): int
    {
        return $words === [] ? 0 : (int) max(1, ceil(count($words) / 2));
    }

    /**
     * Number of words found at a word start in the text; the first six letters
     * are enough so "Glasfaser" also finds "Glasfasern", "Solarpanel" "Solarpanels".
     */
    public static function relevance(array $words, string $text): int
    {
        $text = mb_strtolower($text);

        return count(array_filter($words, fn (string $w) => preg_match(
            '/(?<![\pL\pN])'.preg_quote(mb_substr($w, 0, 6), '/').'/u',
            $text
        ) === 1));
    }

    protected static function haystack(File $file): string
    {
        $tags = method_exists($file, 'tags') ? $file->tags->pluck('name')->implode(' ') : '';

        return implode(' ', [
            (string) $file->alt_text,
            (string) $file->description,
            $tags,
            (string) $file->getFirstMedia('file')?->file_name,
        ]);
    }
}
