<?php

namespace Motor\Admin\Http\Requests\Api;

use Motor\Admin\Http\Requests\Request;

/**
 * Class PermissionGroupRequest
 *
 * @OA\Schema(
 *   schema="PermissionGroupRequest",
 *
 *   @OA\Property(
 *     property="name",
 *     type="string",
 *     example="administration"
 *   ),
 *   required={"name"},
 * )
 */
class PermissionGroupRequest extends Request
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
            'name' => 'required',
        ];
    }
}
