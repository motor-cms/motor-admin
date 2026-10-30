<?php

namespace Motor\Admin\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Motor\Admin\Models\Permission;
use Motor\Admin\Models\User;

class PermissionPolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     *
     * @param  string  $ability
     * @return void|bool
     */
    public function before(User $user, $ability)
    {
        if ($user->hasRole('SuperAdmin')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return $user->hasPermissionTo('permissions.read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return mixed
     */
    public function view(User $user, Permission $permission)
    {
        return $user->hasPermissionTo('permissions.read');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return mixed
     */
    public function create(User $user)
    {
        // ZRMDEV-237: changing permissions grants privileges, so only SuperAdmins
        // (allowed in before()) may do it; permissions.write alone is not enough.
        return false;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return mixed
     */
    public function update(User $user, Permission $permission)
    {
        // ZRMDEV-237: changing permissions grants privileges, so only SuperAdmins
        // (allowed in before()) may do it; permissions.write alone is not enough.
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return mixed
     */
    public function delete(User $user, Permission $permission)
    {
        // ZRMDEV-237: changing permissions grants privileges, so only SuperAdmins
        // (allowed in before()) may do it; permissions.delete alone is not enough.
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return mixed
     */
    public function restore(User $user, Permission $permission)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return mixed
     */
    public function forceDelete(User $user, Permission $permission)
    {
        //
    }
}
