<?php

namespace Motor\Admin\Http\Resources\V2;

use Illuminate\Http\Request;
use Motor\Admin\Models\Client;
use Motor\Core\Http\Resources\V2\BaseResource;

/**
 * V2 ClientResource with standardized envelope.
 *
 * @mixin Client
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property bool $is_active
 */
class ClientResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'address' => $this->address,
            'zip' => $this->zip,
            'city' => $this->city,
            'country_iso_3166_1' => $this->country_iso_3166_1,
            'website' => $this->website,
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,
            'contact_name' => $this->contact_name,
            'contact_phone' => $this->contact_phone,
            'contact_email' => $this->contact_email,
            'frontend_config' => $this->frontend_config ?? (object) [],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
