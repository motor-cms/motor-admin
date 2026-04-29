<?php

namespace Motor\Admin\Services;

use Motor\Admin\Models\ConfigVariable;
use Motor\Core\Filter\Renderers\SelectRenderer;
use Motor\Core\Filter\Renderers\WhereRenderer;

/**
 * Class ConfigVariableService
 */
class ConfigVariableService extends BaseService
{
    protected string $model = ConfigVariable::class;

    public function filters(): void
    {
        $this->filter->add(new SelectRenderer('package'))
            ->setOptions(ConfigVariable::distinct()->pluck('package', 'package'));
        $this->filter->add(new SelectRenderer('group'))
            ->setOptions(ConfigVariable::distinct()->pluck('group', 'group'));
        $this->filter->add(new WhereRenderer('is_invisible'));
    }
}
