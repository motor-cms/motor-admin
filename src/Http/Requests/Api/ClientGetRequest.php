<?php

namespace Motor\Admin\Http\Requests\Api;

class ClientGetRequest extends PaginatedGetRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array[]
     */
    public function rules(): array
    {
        return parent::rules() + [
            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}
