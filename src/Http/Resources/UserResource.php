<?php

namespace Motor\Admin\Http\Resources;

class UserResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function toArray($request): array
    {
        return [
            'id'          => (int) $this->id,
            'clients'     => ClientResource::collection($this->clients),
            'roles'       => RoleResource::collection($this->roles),
            'permissions' => PermissionResource::collection($this->roles->flatMap->permissions->unique('id')),
            'name'        => $this->name,
            'email'       => $this->email,
            'avatar'      => new MediaResource($this->getFirstMedia('avatar')),
        ];
    }
}
