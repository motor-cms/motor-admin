<?php

namespace Motor\Admin\Http\Resources;

class PermissionGroupResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
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
