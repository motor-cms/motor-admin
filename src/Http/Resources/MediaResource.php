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

        $conversions = [];
        if (! is_null($this->generated_conversions)) {
            foreach ($this->generated_conversions as $conversion => $status) {
                if ($status) {
                    if ($this->mime_type === 'image/gif') {
                        $conversions[$conversion] = url($this->getUrl());
                    } else {
                        $conversions[$conversion] = url($this->getUrl($conversion));
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
            'url'         => url($this->getUrl()),
            'path'        => $this->getPath(),
            'uuid'        => $this->uuid,
            'created_at'  => (string) $this->created_at,
            'conversions' => $conversions,
        ];
    }
}
