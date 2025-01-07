<?php

namespace Motor\Admin\Http\Resources;

class DomainCollection extends BaseCollection
{
    /**
     * Transform the resource collection into an array.
     */
    public function toArray(\Illuminate\Http\Request $request): array
    {
        return parent::toArray($request);
    }
}
