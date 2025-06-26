<?php

namespace Motor\Admin\Http\Requests\Api;

use Motor\Admin\Http\Requests\Request;

class LanguagePostRequest extends Request
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
            'iso_639_1' => [
                'required',
                'min:2',
                'max:2',
            ],
            'english_name' => [
                'required',
            ],
            'native_name' => [
                'required',
            ],
        ];
    }
}
