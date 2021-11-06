<?php

namespace Motor\Admin\Http\Requests\Api;

use Motor\Admin\Http\Requests\Request;

/**
 * Class UserRequest
 *
 * @package Motor\Admin\Http\Requests\Admin
 */
class UserPatchRequest extends Request
{
    /**
     * @OA\Schema(
     *   schema="UserPatchRequest",
     *   @OA\Property(
     *     property="client_id",
     *     type="integer",
     *     example="1"
     *   ),
     *   @OA\Property(
     *     property="name",
     *     type="string",
     *     example="My beautiful name"
     *   ),
     *   @OA\Property(
     *     property="email",
     *     type="string",
     *     example="test@domain.com",
     *     description="Must be a unique RFC valid email address"
     *   ),
     *   @OA\Property(
     *     property="password",
     *     type="string",
     *     example="secret password"
     *   ),
     *   @OA\Property(
     *     property="roles",
     *     type="array",
     *     @OA\Items(
     *       ref="#/components/schemas/RoleRequest"
     *     )
     *   ),
     *   @OA\Property(
     *     property="permissions",
     *     type="array",
     *     @OA\Items(
     *       ref="#/components/schemas/PermissionRequest"
     *     )
     *   ),
     *   @OA\Property(
     *     property="avatar",
     *     type="string",
     *     example="ABCDEF",
     *     description="base64 data url"
     *   ),
     *   required={"name", "email"},
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
            'client_id'   => 'nullable|exists:clients',
            'name'        => 'required',
            'email'       => 'nullable|unique:users',
            'password'    => 'required|min:8',
            'roles'       => 'nullable|array',
            'permissions' => 'nullable|array',
            'avatar'      => 'nullable',
        ];
    }
}
