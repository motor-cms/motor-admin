<?php

namespace Motor\Admin\Http\Resources\Frontend;

use Illuminate\Http\Request;
use Motor\Admin\Http\Resources\BaseResource;
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
        $host = $this->host;
        switch ($this->protocol) {
            case 'http':
                if ($this->port != '80') {
                    $host .= ':'.$this->port;
                }
                break;
            case 'https':
                if ($this->port != '443') {
                    $host .= ':'.$this->port;
                }
                break;
        }

        return [
            'url'  => $this->protocol.'://'.$host.$this->path,
            'host' => $this->host,
        ];
    }
}
