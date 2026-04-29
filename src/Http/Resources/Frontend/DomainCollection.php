<?php

namespace Motor\Admin\Http\Resources\Frontend;

use Illuminate\Http\Request;
use Motor\Admin\Http\Resources\BaseCollection;

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
