<?php

namespace Motor\Admin\Http\Requests\Api;

use Motor\Admin\Http\Requests\Request;

class AIHelpPostRequest extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'prompt' => [
                'required',
            ],
            'system_prompt' => [
                'required',
                'exists:ai_system_prompts,id'
            ]
        ];
    }
}
