<?php

namespace Motor\Admin\Http\Resources;

use Illuminate\Http\Request;

class DomainCollection extends BaseCollection
{
    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
