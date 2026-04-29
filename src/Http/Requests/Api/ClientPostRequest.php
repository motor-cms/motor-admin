<?php

namespace Motor\Admin\Http\Requests\Api;

use Motor\Admin\Http\Requests\Request;

class ClientPostRequest extends Request
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
                'string',
            ],
            'slug' => [
                'required',
                'string',
            ],
            'address' => [
                'required',
                'string',
            ],
            'zip' => [
                'required',
                'string',
            ],
            'city' => [
                'required',
                'string',
            ],
            'country_iso_3166_1' => [
                'required',
                'string',
                'min:2',
                'max:2',
            ],
            'is_active' => [
                'required',
                'boolean',
            ],
            'contact_name' => [
                'required',
                'string',
            ],
            'contact_email' => [
                'required',
                'email',
            ],
            'contact_phone' => [
                'required',
                'string',
            ],
            'website' => [
                'nullable',
                'url',
            ],
            'description' => [
                'nullable',
                'string',
            ],
        ];
    }
}
