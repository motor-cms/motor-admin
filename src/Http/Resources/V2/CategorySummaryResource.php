<?php

namespace Motor\Admin\Http\Resources\V2;

use Motor\Admin\Models\Category;
use Motor\Core\Http\Resources\V2\BaseResource;

/**
 * @mixin Category
 */
class CategorySummaryResource extends BaseResource
{
    public function toArray($request): array
    {
        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'scope' => $this->scope,
        ];
    }
}
