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
    protected string $model = Role::class;

    protected array $loadColumns = ['permissions'];

    public function filters(): void
    {
        $this->filter->add(new SelectRenderer('guard_name'))
            ->setOptions(Role::distinct()->pluck('guard_name', 'guard_name'));
    }

    public function afterCreate(): void
    {
        $permissionIds = Arr::get($this->data, 'permissions', []);
        if (! empty($permissionIds)) {
            $permissions = Permission::whereIn('id', $permissionIds)->get();
            $this->record->givePermissionTo($permissions);
        }
    }

    public function afterUpdate(): void
    {
        $permissionIds = Arr::get($this->data, 'permissions', []);
        $permissions = Permission::whereIn('id', $permissionIds)->get();
        $this->record->syncPermissions($permissions);
    }
}
