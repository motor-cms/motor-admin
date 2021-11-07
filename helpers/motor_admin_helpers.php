<?php

use Motor\Admin\Models\ConfigVariable;

/**
 * @param $package
 * @throws \Psr\Container\ContainerExceptionInterface
 * @throws \Psr\Container\NotFoundExceptionInterface
 */
function merge_local_config_with_db_configuration_variables($package)
{
    try {
        foreach (ConfigVariable::where('package', $package)
                               ->get() as $configVariable) {
            $config = app('config')->get($configVariable->group, []);
            app('config')->set($configVariable->group, array_replace_recursive($config, [$configVariable->name => $configVariable->value]));
        }
    } catch (\Exception $e) {
        // Do nothing if the database doesn't exist
    }
}

/**
 * @param string $var
 * @param null $default
 * @return \Illuminate\Database\Eloquent\HigherOrderBuilderProxy|mixed|string|null
 */
function config_variable(string $var, $default = null)
{
    [$package, $group, $name] = explode('.', $var);

    $variable = \Motor\Admin\Models\ConfigVariable::where('package', $package)
                                                  ->where('group', $group)
                                                  ->where('name', $name)
                                                  ->first();
    if (! is_null($variable)) {
        return $variable->value;
    }

    return $default;
}
