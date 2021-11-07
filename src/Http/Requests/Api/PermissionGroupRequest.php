<?php

namespace Motor\Admin\Http\Requests\Api;

use Motor\Admin\Http\Requests\Request;

/**
 * Class PermissionGroupRequest
 *
 * @package Motor\Admin\Http\Requests\Admin
 */
class PermissionGroupRequest extends Request
{
    /**
     * @OA\Schema(
     *   schema="PermissionGroupRequest",
     *   @OA\Property(
     *     property="name",
     *     type="string",
     *     example="administration"
     *   ),
     *   required={"name"},
     * )
     */

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'name' => 'required',
        ];
    }
}
