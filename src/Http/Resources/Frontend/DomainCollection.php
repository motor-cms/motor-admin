<?php

namespace Motor\Admin\Http\Resources\Frontend;

class DomainCollection extends \Motor\Admin\Http\Resources\BaseCollection
{
    /**
     * Transform the resource collection into an array.
     */
    public function toArray(\Illuminate\Http\Request $request): array
    {
        return parent::toArray($request);
    }
}
