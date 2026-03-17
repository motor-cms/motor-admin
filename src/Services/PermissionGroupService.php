<?php

namespace Motor\Admin\Services;

use Illuminate\Support\Arr;
use Motor\Admin\Models\Permission;
use Motor\Admin\Models\PermissionGroup;

/**
 * Class PermissionGroupService
 */
class PermissionGroupService extends BaseService
{
    protected $model = PermissionGroup::class;

    public function afterCreate(): void
    {
        $this->syncPermissions();
    }

    public function afterUpdate(): void
    {
        $this->syncPermissions();
    }

    private function syncPermissions(): void
    {
        $permissionIds = Arr::get($this->data, 'permissions');

        if ($permissionIds === null) {
            return;
        }

        // Unassign permissions currently in this group but not in the new list
        Permission::where('permission_group_id', $this->record->id)
            ->whereNotIn('id', $permissionIds)
            ->update(['permission_group_id' => null]);

        // Assign the new permissions to this group
        if (! empty($permissionIds)) {
            Permission::whereIn('id', $permissionIds)
                ->update(['permission_group_id' => $this->record->id]);
        }
    }
}
