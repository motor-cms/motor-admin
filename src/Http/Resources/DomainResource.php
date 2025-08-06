<?php

namespace Motor\Admin\Http\Resources;

class DomainResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(\Illuminate\Http\Request $request): array
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
