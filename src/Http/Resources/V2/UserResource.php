<?php

namespace Motor\Admin\Http\Resources\V2;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Motor\Admin\Models\User;
use Motor\Core\Http\Resources\V2\BaseResource;

/**
 * V2 UserResource with standardized envelope.
 *
 * @mixin User
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $avatar
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class UserResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar'      => new MediaResource($this->getFirstMedia('avatar')),
            'clients' => $this->whenLoaded('clients', fn () => ClientResource::collection($this->clients)),
            'roles' => $this->whenLoaded('roles', fn () => RoleResource::collection($this->roles)),
            'permissions' => $this->whenLoaded('permissions', fn () => PermissionResource::collection($this->permissions)),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
