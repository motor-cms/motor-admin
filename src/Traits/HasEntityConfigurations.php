<?php

namespace Motor\Admin\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Motor\Admin\Models\EntityConfiguration;

trait HasEntityConfigurations
{
    public function entityConfigurations(): MorphMany
    {
        return $this->morphMany(EntityConfiguration::class, 'configurable');
    }

    public function getConfigValue(string $package, string $group, string $name): ?string
    {
        return $this->entityConfigurations()
            ->whereHas('configVariable', fn ($q) => $q
                ->where('package', $package)
                ->where('group', $group)
                ->where('name', $name)
            )
            ->first()
            ?->value;
    }

    public function setConfigValue(string $package, string $group, string $name, ?string $value): EntityConfiguration
    {
        $configVariable = \Motor\Admin\Models\ConfigVariable::where('package', $package)
            ->where('group', $group)
            ->where('name', $name)
            ->firstOrFail();

        return $this->entityConfigurations()->updateOrCreate(
            ['config_variable_id' => $configVariable->id],
            ['value' => $value]
        );
    }
}
