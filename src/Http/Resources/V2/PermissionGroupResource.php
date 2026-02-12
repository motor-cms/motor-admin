<?php

namespace Motor\Admin\Http\Resources\V2;

use Illuminate\Http\Request;
use Motor\Core\Http\Resources\V2\BaseResource;

class PermissionGroupResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'sort_position' => $this->sort_position,
            'permission_names' => $this->whenLoaded('permissions', fn () => $this->permissions->pluck('name')->values()->all()),
            'permissions' => $this->whenLoaded('permissions', fn () => PermissionResource::collection($this->permissions)),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
