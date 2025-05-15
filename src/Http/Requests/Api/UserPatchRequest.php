<?php

namespace Motor\Admin\Http\Requests\Api;

use Motor\Admin\Http\Requests\Request;

class UserPatchRequest extends Request
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
                \Illuminate\Validation\Rule::unique('users')
                    ->ignore($this->route('user')),
            ],
            'password'       => [
                'nullable',
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
            'avatar.name' => [
                'nullable',
                'string',
            ],
        ];
    }
}
