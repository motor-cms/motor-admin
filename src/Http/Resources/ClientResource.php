<?php

namespace Motor\Admin\Http\Resources;

use Illuminate\Http\Request;
use Motor\Admin\Models\Client;

/**
 * @mixin Client
 */
class ClientResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     */
    public function toArray($request): array
    {
        return [
            'id'                 => (int) $this->id,
            'name'               => $this->name,
            'slug'               => $this->slug,
            'address'            => $this->address,
            'zip'                => $this->zip,
            'city'               => $this->city,
            'country_iso_3166_1' => $this->country_iso_3166_1,
            'website'            => $this->website,
            'description'        => $this->description,
            'is_active'          => (bool) $this->is_active,
            'contact_name'       => $this->contact_name,
            'contact_phone'      => $this->contact_phone,
            'contact_email'      => $this->contact_email,

        ];
    }
}
