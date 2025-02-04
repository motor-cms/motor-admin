<?php

namespace Motor\Admin\Http\Requests\Api;

use Motor\Admin\Http\Requests\Request;

/**
 * Class ProfileEditRequest
 *
 * @OA\Schema(
 *   schema="ProfileEditRequest",
 *
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
class ProfileEditRequest extends Request
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
            'name'     => 'required',
            'email'    => 'required|email',
            'password' => 'nullable|min:8',
            'avatar'   => 'nullable',
        ];
    }
}
