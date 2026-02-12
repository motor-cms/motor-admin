<?php

namespace Motor\Admin\Http\Requests\Api;

use Motor\Admin\Http\Requests\Request;

class DomainPostRequest extends Request
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
            'client_id' => [
                'required',
                'exists:clients,id',
            ],
            'is_active' => [
                'required',
                'boolean',
            ],
            'name' => [
                'required',
            ],
            'protocol' => [
                'required',
            ],
            'host' => [
                'required',
            ],
            'port' => [
                'required',
                'integer',
            ],
            'path' => [
                'required',
            ],
        ];
    }
}
