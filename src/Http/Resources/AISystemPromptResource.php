<?php

namespace Motor\Admin\Http\Resources;

use Illuminate\Http\Request;
use Motor\Admin\Models\AISystemPrompt;

/**
 * @mixin AISystemPrompt
 */
class AISystemPromptResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     */
    public function toArray($request): array
    {
        return [
            'id'        => (int) $this->id,
            'client'    => new ClientResource($this->client),
            'client_id' => (int) $this->client_id,
            'name'      => $this->name,
            'prompt'    => $this->prompt,
        ];
    }
}
