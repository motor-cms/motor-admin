<?php

namespace Motor\Admin\Http\Requests\Api\V2;

use Motor\Admin\Http\Requests\Request;

/**
 * "Mit KI erzeugen" for one image slot. Authorization (tenant switch, tenant access, ai.use, image
 * switch) happens in the controller, the rate limit after it.
 */
class AIImageGeneratePostRequest extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id'       => ['required', 'integer'],
            'prompt'          => ['required', 'string', 'min:3', 'max:1000'],
            'alt_text'        => ['sometimes', 'nullable', 'string', 'max:190'],
            'description'     => ['sometimes', 'nullable', 'string', 'max:1000'],
            'query'           => ['sometimes', 'nullable', 'string', 'max:200'],
            'photorealistic'  => ['sometimes', 'boolean'],
            'caption_visible' => ['sometimes', 'boolean'],
            'contains_people' => ['sometimes', 'nullable', 'boolean'],
        ];
    }
}
