<?php

namespace Motor\Admin\Services;

use Motor\Admin\Models\EntityConfiguration;
use Motor\Core\Filter\Renderers\WhereRenderer;

/**
 * Class EntityConfigurationService
 */
class EntityConfigurationService extends BaseService
{
    protected string $model = EntityConfiguration::class;

    public function filters(): void
    {
        $this->filter->add(new WhereRenderer('configurable_type'));
        $this->filter->add(new WhereRenderer('configurable_id'));
        $this->filter->add(new WhereRenderer('config_variable_id'));
    }

    public function beforeCreate(): void
    {
        $this->validateConfigurableType();
    }

    public function beforeUpdate(): void
    {
        $this->validateConfigurableType();
    }

    protected function validateConfigurableType(): void
    {
        $type = $this->request->get('configurable_type');
        if ($type && ! class_exists($type)) {
            throw new \InvalidArgumentException("Invalid configurable_type: {$type}");
        }
    }
}
