<?php

namespace Motor\Admin\Http\Resources;

class CategoryTreeResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function toArray($request): array
    {
        return [
            'id'       => (int) $this->id,
            'name'     => $this->name,
            'scope'    => $this->scope,
            'children' => CategoryResource::collection($this->whenLoaded('children')),
        ];
    }
}
