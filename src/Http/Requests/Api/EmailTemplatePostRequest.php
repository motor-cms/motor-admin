<?php

namespace Motor\Admin\Http\Requests\Api;

use Motor\Admin\Http\Requests\Request;

class EmailTemplatePostRequest extends Request
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
            'client_id' => [
                'required',
                'integer',
                'exists:clients,id',
            ],
            'language_id' => [
                'required',
                'integer',
                'exists:languages,id',
            ],
            'name' => [
                'required',
            ],
            'slug' => [
                'nullable',
            ],
            'subject' => [
                'required',
            ],
            'body_text' => [
                'nullable',
            ],
            'body_html' => [
                'nullable',
            ],
            'default_sender_name' => [
                'nullable',
            ],
            'default_sender_email' => [
                'nullable',
                'email',
            ],
            'default_recipient_name' => [
                'nullable',
            ],
            'default_recipient_email' => [
                'nullable',
                'email',
            ],
            'default_cc_email' => [
                'nullable',
            ],
            'default_bcc_email' => [
                'nullable',
            ],
            'default_replyto_name' => [
                'nullable',
            ],
            'default_replyto_email' => [
                'nullable',
            ],
        ];
    }
}
