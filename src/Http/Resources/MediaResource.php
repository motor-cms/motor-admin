<?php

namespace Motor\Admin\Http\Resources;

use Motor\Admin\Helpers\Filesize;

/**
 * @OA\Schema(
 *   schema="MediaResourceConversions",
 *
 *   @OA\Property(
 *     property="thumb",
 *     type="string",
 *     example="http://localhost/media/conversion/my-image-thumb.png"
 *   ),
 *   @OA\Property(
 *     property="preview",
 *     type="string",
 *     example="http://localhost/media/conversion/my-image-preview.png"
 *   ),
 * )
 *
 * @OA\Schema(
 *   schema="MediaResource",
 *
 *   @OA\Property(
 *     property="collection",
 *     type="string",
 *     example="images"
 *   ),
 *   @OA\Property(
 *     property="name",
 *     type="string",
 *     example="my-image"
 *   ),
 *   @OA\Property(
 *     property="file_name",
 *     type="string",
 *     example="my-image.png"
 *   ),
 *   @OA\Property(
 *     property="size",
 *     type="integer",
 *     example="31337"
 *   ),
 *   @OA\Property(
 *     property="mime_type",
 *     type="string",
 *     example="image/png"
 *   ),
 *   @OA\Property(
 *     property="url",
 *     type="string",
 *     example="http://localhost/media/my-image.png"
 *   ),
 *   @OA\Property(
 *     property="path",
 *     type="string",
 *     example="/var/www/htdocs/media/my-image.png"
 *   ),
 *   @OA\Property(
 *     property="uuid",
 *     type="string",
 *     example="635b4063-eae8-4d8f-ac45-f29611f5daa0"
 *   ),
 *   @OA\Property(
 *     property="created_at",
 *     type="datetime",
 *     example="2021-04-22 16:23:40"
 *   ),
 *   @OA\Property(
 *     property="conversions",
 *     type="object",
 *     ref="#/components/schemas/MediaResourceConversions"
 *   )
 * )
 */
class MediaResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function toArray($request): array
    {
        $conversions = [];
        if (! is_null($this->generated_conversions)) {
            foreach ($this->generated_conversions as $conversion => $status) {
                if ($status) {
                    if ($this->mime_type === 'image/gif') {
                        $conversions[$conversion] = asset($this->getUrl());
                    } else {
                        $conversions[$conversion] = asset($this->getUrl($conversion));
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
            'url'         => $this->getFullUrl(),
            'path'        => $this->getPath(),
            'uuid'        => $this->uuid,
            'created_at'  => (string) $this->created_at,
            'conversions' => $conversions,
        ];
    }
}
