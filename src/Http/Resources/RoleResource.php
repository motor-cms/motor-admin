<?php

namespace Motor\Admin\Http\Resources;

class RoleResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'guard_name'  => $this->guard_name,
            'permissions' => $this->whenLoaded('permissions', fn () => PermissionResource::collection($this->permissions)),
        ];
    }
}
