<?php

namespace Motor\Admin\Http\Resources;

use Illuminate\Http\Request;

class PermissionGroupCollection extends BaseCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  Request  $request
     */
    public function toArray($request): array
    {
        return parent::toArray($request);
    }
}
