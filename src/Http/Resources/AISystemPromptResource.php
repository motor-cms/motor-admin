<?php

namespace Motor\Admin\Http\Resources;

class AISystemPromptResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function toArray($request): array
    {
        return [
            'id'                      => (int) $this->id,
            'client'                  => new ClientResource($this->client),
            'client_id'               => (int) $this->client_id,
            'name'                    => $this->name,
            'prompt'                  => $this->prompt,
        ];
    }
}
