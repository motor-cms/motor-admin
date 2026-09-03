<?php

namespace Motor\Admin\Http\Resources\V2;

use Illuminate\Http\Request;
use Motor\Admin\Models\Permission;
use Motor\Core\Http\Resources\V2\BaseResource;

/**
 * @mixin Permission
 */
class PermissionResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'guard_name' => $this->guard_name,
            'permission_group' => $this->whenLoaded('permission_group', fn () => new PermissionGroupResource($this->permission_group)),
            /** @format date-time */
            'created_at' => $this->created_at?->toIso8601String(),
            /** @format date-time */
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
