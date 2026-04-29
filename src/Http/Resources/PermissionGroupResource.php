<?php

namespace Motor\Admin\Http\Resources;

use Illuminate\Http\Request;
use Motor\Admin\Models\PermissionGroup;

/**
 * @mixin PermissionGroup
 */
class PermissionGroupResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     */
    public function toArray($request): array
    {
        return [
            'id'            => (int) $this->id,
            'name'          => $this->name,
            'sort_position' => (int) $this->sort_position,
            'permissions'   => PermissionResource::collection($this->whenLoaded('permissions')),
        ];
    }
}
