<?php

namespace Motor\Admin\Http\Requests\Api;

class EntityConfigurationGetRequest extends PaginatedGetRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array[]
     */
    public function rules(): array
    {
        return parent::rules() + [
            'configurable_type' => [
                'sometimes',
                'string',
            ],
            'configurable_id' => [
                'sometimes',
                'integer',
            ],
            'config_variable_id' => [
                'sometimes',
                'integer',
            ],
        ];
    }
}
