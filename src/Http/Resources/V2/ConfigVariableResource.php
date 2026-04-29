<?php

namespace Motor\Admin\Http\Resources\V2;

use Illuminate\Http\Request;
use Motor\Admin\Models\ConfigVariable;
use Motor\Core\Http\Resources\V2\BaseResource;

/**
 * @mixin ConfigVariable
 */
class ConfigVariableResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'package' => $this->package,
            'group' => $this->group,
            'name' => $this->name,
            'value' => $this->value,
            'is_invisible' => (bool) $this->is_invisible,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
