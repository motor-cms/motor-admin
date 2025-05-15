<?php

namespace Motor\Admin\Http\Requests\Api;

use Motor\Admin\Http\Requests\Request;

class PaginatedGetRequest extends Request
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
            'search'      => [
                'string',
            ],
            'page'       => [
                'integer',
            ],
            'per_page'   => [
                'integer',
            ],
        ];
    }
}
