<?php

namespace Motor\Admin\Http\Resources;

use Illuminate\Http\Request;
use Motor\Admin\Models\Domain;

/**
 * @mixin Domain
 */
class DomainResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'        => (int) $this->id,
            'client'    => new ClientResource($this->client),
            'client_id' => $this->client_id,
            'is_active' => (bool) $this->is_active,
            'name'      => $this->name,
            'protocol'  => $this->protocol,
            'host'      => $this->host,
            'port'      => $this->port,
            'path'      => $this->path,
        ];
    }
}
