<?php

namespace Motor\Admin\Http\Requests\Api;

use Motor\Admin\Http\Requests\Request;

/**
 * Class UserPostRequest
 *
 * @package Motor\Admin\Http\Requests\Admin
 */
class UserPostRequest extends Request
{
    /**
     * @OA\Schema(
     *   schema="UserPostRequest",
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
     *       anyOf={
     *         @OA\Schema(type="integer")
     *       }
     *     ),
     *     example="[1,2,3]"
     *   ),
     *   @OA\Property(
     *     property="permissions",
     *     type="array",
     *     @OA\Items(
     *       anyOf={
     *         @OA\Schema(type="integer")
     *       }
     *     ),
     *     example="[1,2,3]"
     *   ),
     *   @OA\Property(
     *     property="avatar",
     *     type="object",
     *     ref="#/components/schemas/FileUpload",
     *     description="If false, the existing avatar will be deleted"
     *   ),
     *   required={"name", "email", "password"},
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
            'client_id'      => 'nullable|integer|exists:clients',
            'name'           => 'required',
            'email'          => 'required|unique:users',
            'password'       => 'required|min:8',
            'roles'          => 'nullable|array',
            'permissions'    => 'nullable|array',
            'avatar'         => 'nullable',
            'avatar.dataUrl' => 'nullable|string',
            'avatar.name'    => 'nullable|string',
        ];
    }
}
