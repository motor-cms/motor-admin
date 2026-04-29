<?php

namespace Motor\Admin\Http\Resources\V2;

use Illuminate\Http\Request;
use Motor\Admin\Models\EntityConfiguration;
use Motor\Core\Http\Resources\V2\BaseResource;

/**
 * @mixin EntityConfiguration
 */
class EntityConfigurationResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'configurable_type' => $this->configurable_type,
            'configurable_id' => $this->configurable_id,
            'config_variable_id' => (int) $this->config_variable_id,
            'config_variable' => $this->whenLoaded('configVariable',
                fn () => new ConfigVariableResource($this->configVariable)),
            'value' => $this->value,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
