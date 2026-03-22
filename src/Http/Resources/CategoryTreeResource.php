<?php

namespace Motor\Admin\Http\Resources;

use Illuminate\Http\Request;
use Motor\Admin\Models\Category;

/**
 * @mixin Category
 */
class CategoryTreeResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     */
    public function toArray($request): array
    {
        return [
            'id'       => (int) $this->id,
            'name'     => $this->name,
            'scope'    => $this->scope,
            'children' => CategoryResource::collection($this->whenLoaded('children')),
        ];
    }
}
