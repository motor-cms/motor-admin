<?php

namespace Motor\Admin\Http\Requests\Api;

use Motor\Admin\Http\Requests\Request;

/**
 * Class RolePostRequest
 *
 * @OA\Schema(
 *   schema="RolePostRequest",
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
 *   required={"name", "guard_name"},
 * )
 */
class RolePostRequest extends Request
{

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
            'name'       => 'required',
            'guard_name' => 'required',
            'permissions' => 'nullable',
        ];
    }
}
