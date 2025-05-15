<?php

namespace Motor\Admin\Http\Requests\Api;

use Motor\Admin\Http\Requests\Request;

class PermissionPostRequest extends Request
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
     *
     * @return array[]
     */
    public function rules(): array
    {
        return [
            'name'       => [
                'required',
            ],
            'guard_name' => [
                'required',
            ],
            'permission_group_id' => [
                'nullable',
                'exists:permission_groups,id',
            ],
        ];
    }
}
