<?php

namespace Motor\Admin\Http\Resources\V2;

use Illuminate\Http\Request;
use Motor\Admin\Models\Domain;
use Motor\Core\Http\Resources\V2\BaseResource;

/**
 * @mixin Domain
 */
class DomainResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'client' => $this->whenLoaded('client', fn () => new ClientResource($this->client)),
            'client_id' => $this->client_id,
            'is_active' => (bool) $this->is_active,
            'is_preview_domain' => (bool) $this->is_preview_domain,
            'protocol' => $this->protocol,
            'host' => $this->host,
            'port' => (int) $this->port,
            'path' => $this->path,
            'entity_configurations' => $this->whenLoaded('entityConfigurations',
                fn () => EntityConfigurationResource::collection($this->entityConfigurations->load('configVariable'))),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
