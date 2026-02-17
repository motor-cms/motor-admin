<?php

namespace Motor\Admin\Http\Requests\Api;

class DomainGetRequest extends PaginatedGetRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array[]
     */
    public function rules(): array
    {
        return parent::rules() + [
            'client_id' => [
                'sometimes',
                'integer',
            ],
            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}
