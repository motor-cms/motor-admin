<?php

namespace Motor\Admin\Http\Resources\Frontend;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *   schema="DomainResource",
 *
 *   @OA\Property(
 *     property="url",
 *     type="string",
 *     example="https://example.com:443/"
 *   ),
 * )
 */
class DomainResource extends \Motor\Admin\Http\Resources\BaseResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(\Illuminate\Http\Request $request): array
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
