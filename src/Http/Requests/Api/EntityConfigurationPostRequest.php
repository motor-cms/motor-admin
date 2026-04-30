<?php

namespace Motor\Admin\Http\Requests\Api;

use Motor\Admin\Http\Requests\Request;

class EntityConfigurationPostRequest extends Request
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
            'configurable_type' => [
                'required',
                'string',
            ],
            'configurable_id' => [
                'required',
                'integer',
            ],
            'config_variable_id' => [
                'required',
                'exists:config_variables,id',
            ],
            'value' => [
                'nullable',
                'string',
            ],
        ];
    }
}
