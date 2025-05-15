<?php

namespace Motor\Admin\Http\Requests\Api;

use Motor\Admin\Http\Requests\Request;

class CategoryRequest extends Request
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
            'name'                => [
                'required',
            ],
            'parent_id'           => [
                'required',
            ],
            'previous_sibling_id' => [
                'nullable',
            ],
            'next_sibling_id'     => [
                'nullable',
            ],
        ];
    }
}
