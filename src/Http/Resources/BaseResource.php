<?php

namespace Motor\Admin\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class BaseResource extends JsonResource
{
    public function toArrayRecursive()
    {
        $resourceResponse = $this->toResponse(request());

        return Arr::get(json_decode($resourceResponse->getContent(), true), 'data');
    }

    /**
     * Create a new anonymous resource collection.
     *
     * @param mixed $resource
     * @return \Motor\Admin\Http\Resources\AnonymousResourceCollection
     */
    public static function collection($resource): \Motor\Admin\Http\Resources\AnonymousResourceCollection
    {
        return tap(new AnonymousResourceCollection($resource, static::class), function ($collection) {
            if (property_exists(static::class, 'preserveKeys')) {
                $collection->preserveKeys = (new static([]))->preserveKeys === true;
            }
        });
    }
}
