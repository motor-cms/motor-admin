<?php

namespace Motor\Admin\Http\Requests\Api;

use Motor\Admin\Http\Requests\Request;

/**
 * Class RolePatchRequest
 *
 * @OA\Schema(
 *   schema="RolePatchRequest",
 *
 *   @OA\Property(
 *     property="name",
 *     type="string",
 *     example="Administrator"
 *   ),
 *   @OA\Property(
 *     property="guard_name",
 *     type="string",
 *     example="web"
 *   ),
 *   required={"name"},
 * )
 */
class RolePatchRequest extends Request
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
            'name'       => 'required',
            'guard_name' => 'nullable',
        ];
    }
}
