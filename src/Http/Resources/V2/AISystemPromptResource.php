<?php

namespace Motor\Admin\Http\Resources\V2;

use Illuminate\Http\Request;
use Motor\Core\Http\Resources\V2\BaseResource;

class AISystemPromptResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'prompt' => $this->prompt,
            'client' => $this->whenLoaded('client', fn () => new ClientResource($this->client)),
            'client_id' => (int) $this->client_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
