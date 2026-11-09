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

        return $files
            ->filter(fn (File $file) => str_starts_with((string) $file->getFirstMedia('file')?->mime_type, 'image/'))
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
        $words = array_values(array_filter(
            preg_split('/[^\pL\pN]+/u', mb_strtolower($query)) ?: [],
            fn ($w) => mb_strlen($w) > 3
        ));
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
}
