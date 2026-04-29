<?php

namespace Motor\Admin\Http\Requests\Api;

use Illuminate\Validation\Rule;

class LanguagePatchRequest extends LanguagePostRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'iso_639_1' => [
                'required',
                'min:2',
                'max:2',
                Rule::unique('languages', 'iso_639_1')->ignore($this->route('language')),
            ],
        ]);
    }
}
