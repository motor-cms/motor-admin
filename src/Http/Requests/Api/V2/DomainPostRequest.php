<?php

namespace Motor\Admin\Http\Requests\Api\V2;

use Motor\Admin\Http\Requests\Api\DomainPostRequest as V1DomainPostRequest;

class DomainPostRequest extends V1DomainPostRequest
{
    public function rules(): array
    {
        return parent::rules() + [
            'is_preview_domain' => ['sometimes', 'boolean'],
            'is_canonical' => ['sometimes', 'boolean'],
        ];
    }
}
