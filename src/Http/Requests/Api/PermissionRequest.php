<?php

namespace Motor\Admin\Http\Requests\Api;

use Motor\Admin\Http\Requests\Request;

/**
 * Class PermissionRequest
 *
 * @package Motor\Admin\Http\Requests\Admin
 */
class PermissionRequest extends Request
{
    /**
     * @OA\Schema(
     *   schema="PermissionRequest",
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
