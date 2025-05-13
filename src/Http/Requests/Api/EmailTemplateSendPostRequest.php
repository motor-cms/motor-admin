<?php

namespace Motor\Admin\Http\Requests\Api;

use Motor\Admin\Http\Requests\Request;

class EmailTemplateSendPostRequest extends Request
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
            'client_id'         => [
                'required',
                'integer',
            ],
            'language_id'       => [
                'required',
                'integer',
            ],
            'slug'              => [
                'required',
            ],
            'subject'           => [
                'nullable',
            ],
            'body_text'         => [
                'nullable',
            ],
            'body_html'         => [
                'nullable',
            ],
            'sender_name'       => [
                'nullable',
            ],
            'sender_email'      => [
                'nullable',
                'email',
            ],
            'recipient_name'    => [
                'nullable',
            ],
            'recipient_email'   => [
                'nullable',
                'email',
            ],
            'cc_email'          => [
                'nullable',
            ],
            'bcc_email'         => [
                'nullable',
            ],
            'replyto_email'     => [
                'nullable',
            ],
            'replyto_name'      => [
                'nullable',
            ],
            'text_replace_data' => [
                'nullable',
                'array',
            ],
        ];
    }
}
