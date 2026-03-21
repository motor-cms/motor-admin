<?php

namespace Motor\Admin\Http\Resources;

use Illuminate\Http\Request;

class AIHelpResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     */
    public function toArray($request): array
    {
        return [
            'message' => $this->message,
        ];
    }
}
