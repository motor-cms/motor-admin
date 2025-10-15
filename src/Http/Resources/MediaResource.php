<?php

namespace Motor\Admin\Http\Resources;

use Illuminate\Support\Facades\URL;
use Motor\Admin\Helpers\Filesize;

class MediaResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function toArray($request): array
    {
        URL::forceRootUrl(config('app.url'));

        $urlPrefix = $diskUrl = \Storage::disk('media')->url($this->id);

        if (config('filesystems.has_s3')) {
            $s3 = \Storage::disk('media-s3');
            if ($s3->exists($this->id.'/'.$this->file_name)) {
                $urlPrefix = $s3->url($this->id);
            }
        }

        $conversions = [];
        if (! is_null($this->generated_conversions)) {
            foreach ($this->generated_conversions as $conversion => $status) {
                if ($status) {
                    if ($this->mime_type === 'image/gif') {
                        $conversions[$conversion] = $urlPrefix.'/'. $this->file_name;
                    } else {
                        $conversions[$conversion] = str_replace($diskUrl, $urlPrefix, $this->getUrl($conversion));
                    }
                }
            }
        }

        return [
            'collection'  => $this->collection_name,
            'name'        => $this->name,
            'file_name'   => $this->file_name,
            'size'        => (int) $this->size,
            'size_human'  => Filesize::bytesToHuman((int) $this->size),
            'mime_type'   => $this->mime_type,
            'url'         => $urlPrefix.'/'.$this->file_name,
            'path'        => $this->getPath(),
            'uuid'        => $this->uuid,
            'created_at'  => (string) $this->created_at,
            'conversions' => $conversions,
        ];
    }
}
