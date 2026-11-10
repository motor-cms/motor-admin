<?php

namespace Motor\Admin\Services\AI\Images;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Motor\Admin\Models\Client;
use Motor\Admin\Models\User;
use Motor\Media\Models\File;
use Throwable;

/**
 * Puts an image from outside (AI generation, later stock) into the media library of a tenant, like an upload:
 * a File with description, alt text, author and source, the image in the `file` collection on the `media` disk,
 * then the search index.
 *
 * The origin goes into the media row's `custom_properties.origin` (Spatie), so the files table needs no new
 * columns. It is the proof of where the image came from, under which licence and who took it over:
 *
 *   source_type      ai | stock | upload
 *   provider         mistral | pexels | pixabay
 *   source_url       page of the image at the provider (stock), null for AI
 *   source_id        id at the provider (stock) or the tool reference (AI)
 *   author_name      photographer (stock) / null
 *   author_url       profile link (stock) / null
 *   license          e.g. "Pexels License"; AI: "KI-generiert, keine Lizenz eines Dritten"
 *   license_url
 *   attribution_text ready-made credit line for the frontend
 *   credit_required  whether the frontend has to show the credit line
 *   imported_by      user id, imported_by_name
 *   imported_at      ISO date
 *   contains_people  null = unknown; set by the editor or the provider
 *   contains_brands
 *   ai_model, ai_tool, ai_prompt, ai_prompt_revised, ai_generated_at, photorealistic, caption_visible
 *   marking          c2pa | xmp | none (machine-readable marking in the file)
 *   provider_response  stock: API answer at the time of import (licence proof)
 */
class MediaImporter
{
    /**
     * @param  array{description: string, alt_text: string, author: string, source: string, ai_labeling?: ?string}  $fields
     * @param  array<string, mixed>  $origin
     */
    public function store(Client $client, User $user, string $bytes, string $mime, string $fileName, array $fields, array $origin): File
    {
        $file = new File([
            'client_id'                     => $client->id,
            'description'                   => mb_substr($fields['description'], 0, 2000),
            'author'                        => mb_substr($fields['author'], 0, 190),
            'source'                        => mb_substr($fields['source'], 0, 190),
            'alt_text'                      => mb_substr($fields['alt_text'], 0, 190),
            'ai_labeling'                   => $fields['ai_labeling'] ?? null,
            'is_global'                     => false,
            'is_excluded_from_search_index' => false,
        ]);
        // The search index must not decide whether the image is kept: with Meilisearch down, saving (Scout syncs
        // on save) threw, the request answered 500 and the image, already paid for, was lost to the editor.
        // Indexing happens below and only logs a failure.
        File::withoutSyncingToSearch(fn () => $file->save());

        $origin = array_merge([
            'imported_by'      => $user->id,
            'imported_by_name' => $user->name,
            'imported_at'      => now()->toIso8601String(),
        ], $origin);

        File::withoutSyncingToSearch(fn () => $file->addMediaFromString($bytes)
            ->usingFileName($fileName)
            ->usingName(pathinfo($fileName, PATHINFO_FILENAME))
            ->withCustomProperties(['origin' => $origin, 'mime_type' => $mime])
            ->toMediaCollection('file', 'media'));

        $file->refresh();
        try {
            $file->searchable();
        } catch (Throwable $e) {
            Log::warning('AI image: media library search index failed, image is stored anyway', ['file_id' => $file->id, 'error' => $e->getMessage()]);
        }

        return $file;
    }

    /** "ki-waermepumpe-vor-dem-haus-20261109-1347.jpg" */
    public static function fileName(string $prefix, string $text, string $extension): string
    {
        $slug = Str::of($text)->ascii('de')->slug()->limit(48, '')->trim('-')->value() ?: 'bild';

        return $prefix.'-'.$slug.'-'.now()->format('Ymd-Hi').'.'.$extension;
    }
}
