<?php

namespace Motor\Admin\Http\Resources\V2;

use Illuminate\Http\Request;
use Motor\Core\Http\Resources\V2\BaseResource;

class DomainResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'client' => $this->whenLoaded('client', fn () => [
                'id' => $this->client->id,
                'name' => $this->client->name,
            ]),
            'client_id' => $this->client_id,
            'is_active' => (bool) $this->is_active,
            'protocol' => $this->protocol,
            'host' => $this->host,
            'port' => $this->port,
            'path' => $this->path,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
