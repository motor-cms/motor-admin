<?php

namespace Motor\Admin\Http\Requests\Api;

class EmailTemplateGetRequest extends PaginatedGetRequest
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
            'language_id' => [
                'sometimes',
                'integer',
            ],
        ];
    }
}
