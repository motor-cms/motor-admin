<?php

namespace Motor\Admin\Http\Requests\Api;

use Motor\Admin\Http\Requests\Request;

/**
 * Class RoleRequest
 *
 * @package Motor\Admin\Http\Requests\Admin
 */
class RoleRequest extends Request
{
    /**
     * @OA\Schema(
     *   schema="RoleRequest",
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

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name'       => 'required',
            'guard_name' => 'nullable',
        ];
    }
}
