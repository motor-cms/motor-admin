<?php

namespace Motor\Admin\Http\Requests\Api\V2;

use Motor\Admin\Http\Requests\Request;

class AIBuilderStatusGetRequest extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'integer'],
        ];
    }
}
