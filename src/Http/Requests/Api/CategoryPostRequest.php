<?php

namespace Motor\Admin\Http\Requests\Api;

use Motor\Admin\Http\Requests\Request;
use Motor\Admin\Rules\MatchScope;

class CategoryPostRequest extends Request
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
            'name' => [
                'required',
            ],
            'parent_id' => [
                'required',
                'exists:categories,id',
                new MatchScope,
            ],
            'previous_sibling_id' => [
                'nullable',
            ],
            'next_sibling_id' => [
                'nullable',
            ],
        ];
    }
}
