<?php

namespace Motor\Admin\Http\Requests\Api;

use Motor\Admin\Http\Requests\Request;

class AISystemPromptPostRequest extends Request
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
                'nullable',
                'exists:clients,id',
            ],
            'name' => [
                'required',
            ],
            'prompt' => [
                'required',
            ],
        ];
    }
}
