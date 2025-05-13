<?php

namespace Motor\Admin\Http\Requests\Api;

use Motor\Admin\Http\Requests\Request;

class UserPostRequest extends Request
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
     * @return array[]
     */
    public function rules(): array
    {
        return [
            'clients'      => [
                'nullable',
                'array',
            ],
            'clients.*'      => [
                'exists:clients,id',
            ],
            'name'           => [
                'required',
            ],
            'email'          => [
                'required',
                'email',
                'unique:users',
            ],
            'password'       => [
                'required',
                'min:8',
            ],
            'roles'          => [
                'nullable',
                'array',
            ],
            'roles.*' => [
                'exists:roles,id',
            ],
            'permissions'    => [
                'nullable',
                'array',
            ],
            'permissions.*'    => [
                'exists:permissions,id',
            ],
            'avatar'         => [
                'nullable',
            ],
            'avatar.dataUrl' => [
                'nullable',
                'string',
            ],
            'avatar.name'    => [
                'nullable',
                'string',
            ],
        ];
    }
}
