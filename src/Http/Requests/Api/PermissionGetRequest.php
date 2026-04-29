<?php

namespace Motor\Admin\Http\Requests\Api;

class PermissionGetRequest extends PaginatedGetRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array[]
     */
    public function rules(): array
    {
        return parent::rules() + [
            'permission_group_id' => [
                'sometimes',
                'integer',
            ],
            'guard_name' => [
                'sometimes',
                'string',
            ],
        ];
    }
}
