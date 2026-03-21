<?php

namespace Motor\Admin\Http\Resources;

use Illuminate\Http\Request;

class ConfigVariableResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     */
    public function toArray($request): array
    {
        return [
            'id'           => (int) $this->id,
            'package'      => $this->package,
            'group'        => $this->group,
            'name'         => $this->name,
            'value'        => $this->value,
            'is_invisible' => (bool) $this->is_invisible,
        ];
    }
}
