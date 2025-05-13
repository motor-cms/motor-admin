<?php

namespace Motor\Admin\Http\Requests\Api;

use Motor\Admin\Http\Requests\Request;

class ClientPostRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
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
            'name'               => [
                'required',
            ],
            'slug'               => [
                'required',
            ],
            'address'            => [
                'nullable',
            ],
            'zip'                => [
                'nullable',
            ],
            'city'               => [
                'nullable',
            ],
            'country_iso_3166_1' => [
                'nullable',
                'min:2',
                'max:2',
            ],
            'website'            => [
                'nullable',
                'url',
            ],
            'description'        => [
                'nullable',
            ],
            'is_active'          => [
                'nullable',
                'boolean',
            ],
            'contact_name'       => [
                'nullable',
            ],
            'contact_email'      => [
                'nullable',
                'email',
            ],
            'contact_phone'      => [
                'nullable',
            ],
        ];
    }
}
