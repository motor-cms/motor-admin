<?php

namespace Motor\Admin\Http\Resources\V2;

use Illuminate\Http\Request;
use Motor\Admin\Models\Category;
use Motor\Core\Http\Resources\V2\BaseResource;

/**
 * V2 CategoryTreeResource - root category with children tree.
 *
 * Children are only included when explicitly eager loaded.
 *
 * @mixin Category
 */
class CategoryTreeResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'scope' => $this->scope,
            // Only include children when explicitly loaded
            'children' => $this->whenLoaded('children', fn () => CategoryResource::collection($this->children)),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
