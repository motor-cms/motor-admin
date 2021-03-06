<?php

namespace Motor\Admin\Http\Requests\Api;

use Motor\Admin\Http\Requests\Request;

/**
 * Class ProfileEditRequest
 *
 * @package Motor\Admin\Http\Requests\Admin
 */
class ProfileEditRequest extends Request
{
    /**
     * @OA\Schema(
     *   schema="ProfileEditRequest",
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
            'name'     => 'required',
            'email'    => 'required|email',
            'password' => 'nullable|min:8',
            'avatar'   => 'nullable',
        ];
    }
}
