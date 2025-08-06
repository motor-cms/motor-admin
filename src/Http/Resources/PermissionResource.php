<?php

namespace Motor\Admin\Http\Resources;

class PermissionResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function toArray($request): array
    {
        return [
            'id'               => (int) $this->id,
            'name'             => $this->name,
            'guard_name'       => $this->guard_name,
            'permission_group' => new PermissionGroupResource($this->whenLoaded('permission_group')),
        ];
    }
}
