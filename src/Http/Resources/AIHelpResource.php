<?php

namespace Motor\Admin\Http\Resources;

class AIHelpResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function toArray($request): array
    {
        return [
            'message' => $this->message,
        ];
    }
}
