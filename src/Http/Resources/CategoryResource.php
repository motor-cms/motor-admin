<?php

namespace Motor\Admin\Http\Resources;

class CategoryResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function toArray($request): array
    {
        // if ($request->route()->compiled->getStaticPrefix() === '/api/category_trees') {
        $this->load('children');
        // }

        return [
            'id'        => (int) $this->id,
            'name'      => $this->name,
            'scope'     => $this->scope,
            'parent_id' => (int) $this->parent_id,
            '_lft'      => (int) $this->_lft,
            '_rgt'      => (int) $this->_rgt,
            'level'     => (int) $this->ancestors()
                ->count(),
            'children' => CategoryResource::collection($this->whenLoaded('children')),
        ];
    }
}
