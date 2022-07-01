<?php

namespace Motor\Admin\Services;

use Illuminate\Support\Arr;
use Motor\Admin\Models\Permission;
use Motor\Admin\Models\Role;

/**
 * Class RoleService
 */
class RoleService extends BaseService
{
    protected $model = Role::class;

    public function afterCreate()
    {
        foreach (Arr::get($this->data, 'permissions', []) as $permission) {
            $this->record->givePermissionTo(Permission::find((int) $permission));
        }
    }

    public function afterUpdate()
    {
        foreach (Permission::all() as $permission) {
            $this->record->revokePermissionTo($permission);
        }

        $this->afterCreate();
    }
}
