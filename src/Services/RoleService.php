<?php

namespace Motor\Admin\Services;

use Illuminate\Support\Arr;
use Motor\Admin\Models\Permission;
use Motor\Admin\Models\Role;
use Motor\Core\Filter\Renderers\SelectRenderer;

/**
 * Class RoleService
 */
class RoleService extends BaseService
{
    protected $model = Role::class;

    public function filters(): void
    {
        $this->filter->add(new SelectRenderer('guard_name'))
            ->setOptions(Role::distinct()->pluck('guard_name', 'guard_name'));
    }

    public function afterCreate(): void
    {
        foreach (Arr::get($this->data, 'permissions', []) as $permission) {
            $this->record->givePermissionTo(Permission::find((int) $permission));
        }
    }

    public function afterUpdate(): void
    {
        foreach (Permission::all() as $permission) {
            $this->record->revokePermissionTo($permission);
        }

        $this->afterCreate();
    }
}
