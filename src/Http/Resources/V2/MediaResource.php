<?php

namespace Motor\Admin\Http\Resources\V2;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Motor\Admin\Helpers\Filesize;
use Motor\Core\Http\Resources\V2\BaseResource;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @mixin Media
 */
class MediaResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        URL::forceRootUrl(config('app.url'));
        $prependAppUrl = true;

        $urlPrefix = $diskUrl = Storage::disk('media')->url($this->id);

        if (config('filesystems.has_s3')) {
            $s3 = Storage::disk('media-s3');
            if ($s3->exists('media/'.$this->id.'/'.$this->file_name)) {
                $urlPrefix = $s3->url('media/'.$this->id);
                $prependAppUrl = false;
            }
        }

        $conversions = [];
        if (! is_null($this->generated_conversions)) {
            foreach ($this->generated_conversions as $conversion => $status) {
                if ($status) {
                    if ($this->mime_type === 'image/gif') {
                        $conversions[$conversion] = ($prependAppUrl ? config('app.url') : '').$urlPrefix.'/'.$this->file_name;
                    } else {
                        $conversions[$conversion] = ($prependAppUrl ? config('app.url') : '').str_replace($diskUrl, $urlPrefix, $this->getUrl($conversion));
                    }
                }
            }
        }

        return [
            'collection' => $this->collection_name,
            'name' => $this->name,
            'file_name' => $this->file_name,
            'size' => (int) $this->size,
            'size_human' => Filesize::bytesToHuman((int) $this->size),
            'mime_type' => $this->mime_type,
            'url' => ($prependAppUrl ? config('app.url') : '').$urlPrefix.'/'.$this->file_name,
            'local_url' => config('app.url').$diskUrl.'/'.$this->file_name,
            'path' => $this->getPath(),
            'uuid' => $this->uuid,
            /** @format date-time */
            'created_at' => $this->created_at?->toIso8601String(),
            'conversions' => $conversions,
        ];
    }
}
