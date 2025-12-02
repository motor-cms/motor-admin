<?php

namespace Motor\Admin\Http\Resources\V2;

use Illuminate\Http\Request;
use Motor\Core\Http\Resources\V2\BaseResource;

/**
 * V2 CategoryResource - uses whenLoaded() to avoid recursive type inference.
 *
 * Children are only included when explicitly eager loaded by the controller.
 * This fixes Scramble stack overflow while preserving full tree structure capability.
 */
class CategoryResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'scope' => $this->scope,
            'parent_id' => $this->parent_id ? (int) $this->parent_id : null,
            '_lft' => (int) $this->_lft,
            '_rgt' => (int) $this->_rgt,
            'level' => $this->whenNotNull($this->depth, fn () => (int) $this->depth),
            // Only include children when explicitly loaded - breaks Scramble recursion
            'children' => $this->whenLoaded('children', fn () => CategoryResource::collection($this->children)),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
