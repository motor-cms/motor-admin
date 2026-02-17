<?php

namespace Motor\Admin\Http\Requests\Api;

class ConfigVariableGetRequest extends PaginatedGetRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array[]
     */
    public function rules(): array
    {
        return parent::rules() + [
            'package' => [
                'sometimes',
                'string',
            ],
            'group' => [
                'sometimes',
                'string',
            ],
            'is_invisible' => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}
