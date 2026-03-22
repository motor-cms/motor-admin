<?php

namespace Motor\Admin\Services;

use Motor\Admin\Models\Permission;
use Motor\Core\Filter\Renderers\WhereRenderer;

/**
 * Class PermissionService
 */
class PermissionService extends BaseService
{
    protected string $model = Permission::class;

    public function filters(): void
    {
        $this->filter->add(new WhereRenderer('permission_group_id'));
    }
}
