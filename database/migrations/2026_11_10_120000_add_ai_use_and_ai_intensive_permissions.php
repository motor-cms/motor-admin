<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Motor\Admin\Models\Permission;
use Motor\Admin\Models\PermissionGroup;
use Motor\Admin\Models\Role;
use Motor\Admin\Models\User;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const OLD = 'builder-pages.ai';

    private const NEW = ['ai.use', 'ai.intensive'];

    /** Only these roles get the higher daily budget, and only if they had the old permission. */
    private const INTENSIVE_ROLES = ['SuperAdmin', 'Editor'];

    /**
     * ai.use and ai.intensive replace builder-pages.ai. Every role and every user that had the old permission gets
     * ai.use, so nobody loses access with the deploy. ai.intensive goes only to SuperAdmin and Editor; Writer and all
     * other roles stay on the normal budget. Then the old one goes away.
     */
    public function up(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('permission_groups')) {
            return;
        }

        $this->quietly(function () {
            $group = PermissionGroup::firstOrCreate(['name' => 'ai']);
            collect(self::NEW)->each(fn (string $name) => Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['permission_group_id' => $group->id],
            ));

            $old = Permission::where('name', self::OLD)->where('guard_name', 'web')->first();
            if ($old === null) {
                return;
            }

            foreach (Role::whereHas('permissions', fn ($q) => $q->where('permissions.id', $old->id))->get() as $role) {
                $role->givePermissionTo('ai.use');
                if (in_array($role->name, self::INTENSIVE_ROLES, true)) {
                    $role->givePermissionTo('ai.intensive');
                }
            }
            foreach (User::whereHas('permissions', fn ($q) => $q->where('permissions.id', $old->id))->get() as $user) {
                $user->givePermissionTo('ai.use');
            }

            $old->delete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $this->quietly(function () {
            $use = Permission::where('name', 'ai.use')->where('guard_name', 'web')->first();
            if ($use !== null) {
                $builderGroup = PermissionGroup::firstOrCreate(['name' => 'builder-pages']);
                $old = Permission::firstOrCreate(
                    ['name' => self::OLD, 'guard_name' => 'web'],
                    ['permission_group_id' => $builderGroup->id],
                );
                foreach (Role::whereHas('permissions', fn ($q) => $q->where('permissions.id', $use->id))->get() as $role) {
                    $role->givePermissionTo($old);
                }
                foreach (User::whereHas('permissions', fn ($q) => $q->where('permissions.id', $use->id))->get() as $user) {
                    $user->givePermissionTo($old);
                }
            }

            Permission::whereIn('name', self::NEW)->where('guard_name', 'web')->get()->each->delete();
            PermissionGroup::where('name', 'ai')->delete();
        });
    }

    /** Without search indexing (Scout) and with a fresh permission cache afterwards. */
    private function quietly(callable $callback): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::withoutSyncingToSearch(fn () => PermissionGroup::withoutSyncingToSearch($callback));
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
