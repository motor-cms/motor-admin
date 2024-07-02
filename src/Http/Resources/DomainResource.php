<?php

namespace Motor\Admin\Http\Resources;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *   schema="DomainResource",
 *
 *   @OA\Property(
 *     property="client_id",
 *     type="string",
 *     example="92228459813697"
 *   ),
 *   @OA\Property(
 *     property="is_active",
 *     type="boolean",
 *     example="1"
 *   ),
 *   @OA\Property(
 *     property="name",
 *     type="string",
 *     example="example.com htts config"
 *   ),
 *   @OA\Property(
 *     property="protocol",
 *     type="string",
 *     example="https"
 *   ),
 *   @OA\Property(
 *     property="host",
 *     type="string",
 *     example="example.com"
 *   ),
 *   @OA\Property(
 *     property="port",
 *     type="string",
 *     example="443"
 *   ),
 *   @OA\Property(
 *     property="path",
 *     type="string",
 *     example="/"
 *   ),
 *   @OA\Property(
 *     property="target",
 *     type="string",
 *     example="contact"
 *   ),
 *   @OA\Property(
 *     property="parameters",
 *     type="string",
 *     example="utm_source=example.com&utm_medium=referral&utm_campaign=example.com"
 *   )
 * )
 */
class DomainResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(\Illuminate\Http\Request $request): array
    {
        return [
            'id'         => (int) $this->id,
            'client'     => new ClientResource($this->client),
            'client_id'  => $this->client_id,
            'is_active'  => (bool) $this->is_active,
            'name'       => $this->name,
            'protocol'   => $this->protocol,
            'host'       => $this->host,
            'port'       => $this->port,
            'path'       => $this->path,
        ];
    }
}
