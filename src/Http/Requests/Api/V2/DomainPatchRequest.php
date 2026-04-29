<?php

namespace Motor\Admin\Http\Requests\Api\V2;

use Motor\Admin\Http\Requests\Api\DomainPatchRequest as V1DomainPatchRequest;

class DomainPatchRequest extends V1DomainPatchRequest
{
    public function rules(): array
    {
        return parent::rules() + [
            'is_preview_domain' => ['sometimes', 'boolean'],
        ];
    }
}
