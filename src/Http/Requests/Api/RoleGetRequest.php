<?php

namespace Motor\Admin\Http\Requests\Api;

class RoleGetRequest extends PaginatedGetRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array[]
     */
    public function rules(): array
    {
        return parent::rules() + [
            'guard_name' => [
                'sometimes',
                'string',
            ],
        ];
    }
}
