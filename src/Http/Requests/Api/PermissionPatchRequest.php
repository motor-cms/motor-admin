<?php

namespace Motor\Admin\Http\Requests\Api;

use Motor\Admin\Http\Requests\Request;

/**
 * Class PermissionPatchRequest
 *
 * @OA\Schema(
 *   schema="PermissionPatchRequest",
 *
 *   @OA\Property(
 *     property="name",
 *     type="string",
 *     example="dashboard.read"
 *   ),
 *   @OA\Property(
 *     property="guard_name",
 *     type="string",
 *     example="web"
 *   ),
 *   required={"name"},
 * )
 */
class PermissionPatchRequest extends Request
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
