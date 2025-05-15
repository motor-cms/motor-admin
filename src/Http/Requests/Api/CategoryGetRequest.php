<?php

namespace Motor\Admin\Http\Requests\Api;

class CategoryGetRequest extends PaginatedGetRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array[]
     */
    public function rules(): array
    {
        return parent::rules() + [
            'scope' => [
                'string',
            ],
        ];
    }
}
