<?php

namespace Motor\Admin\Http\Requests\Api;

use Motor\Admin\Http\Requests\Request;

class EntityConfigurationPatchRequest extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
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
                'exists:config_variables,id',
            ],
            'value' => [
                'nullable',
                'string',
            ],
        ];
    }
}
