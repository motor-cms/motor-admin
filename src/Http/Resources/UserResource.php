<?php

namespace Motor\Admin\Http\Resources;

use Illuminate\Http\Request;

class UserResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     */
    public function toArray($request): array
    {
        return [
            'id'          => (int) $this->id,
            'clients'     => $this->whenLoaded('clients', fn () => ClientResource::collection($this->clients)),
            'roles'       => $this->whenLoaded('roles', fn () => RoleResource::collection($this->roles)),
            'permissions' => $this->whenLoaded('roles', fn () => PermissionResource::collection(
                $this->roles->flatMap->permissions
                    ->merge($this->whenLoaded('permissions', fn () => $this->permissions, collect()))
                    ->unique('id')
            )),
            'name'        => $this->name,
            'email'       => $this->email,
            'avatar'      => new MediaResource($this->getFirstMedia('avatar')),
        ];
    }
}
