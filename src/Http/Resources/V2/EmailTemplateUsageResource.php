<?php

namespace Motor\Admin\Http\Resources\V2;

use Illuminate\Http\Request;
use Motor\Core\Http\Resources\V2\BaseResource;

class EmailTemplateUsageResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'form_config_id' => (int) $this->resource['form_config_id'],
            'form_component_uuid' => $this->resource['form_component_uuid'],
            'form_name' => $this->resource['form_name'],
            'custom_content_type_id' => $this->resource['custom_content_type_id'] ? (int) $this->resource['custom_content_type_id'] : null,
            'usage_type' => $this->resource['usage_type'],
            'builder_page' => $this->resource['builder_page'],
        ];
    }
}
