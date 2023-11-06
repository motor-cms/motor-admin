<?php

namespace Motor\Admin\Http\Requests\Api;

use Motor\Admin\Http\Requests\Request;
use OpenApi\Annotations as OA;

/**
 * Class DomainPatchRequest
 *
 * @OA\Schema(
 *   schema="DomainPatchRequest",
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
 *     example="example.com https config"
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
 *   ),
 *   required={"client_id", "is_active", "name", "protocol", "host", "port"},
 * )
 */
class DomainPatchRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'client_id'  => 'required',
            'is_active'  => 'required',
            'name'       => 'required',
            'protocol'   => 'required',
            'host'       => 'required',
            'port'       => 'required',
            'path'       => 'required',
            'target'     => 'nullable',
            'parameters' => 'nullable',
        ];
    }
}
