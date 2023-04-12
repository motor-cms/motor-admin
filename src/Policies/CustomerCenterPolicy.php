<?php

namespace Motor\Admin\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Motor\Admin\Models\CustomerCenter;
use Motor\Admin\Models\Role;
use Motor\Admin\Models\User;

class CustomerCenterPolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     *
     * @param  \Motor\Admin\Models\User  $user
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
     * @param  \Motor\Admin\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return $user->hasPermissionTo('customer_center.read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \Motor\Admin\Models\User  $user
     * @param  \Motor\Admin\Models\CustomerCenter  $customer_center
     * @return mixed
     */
    public function view(User $user, CustomerCenter $customer_center)
    {
        return $user->hasPermissionTo('customer_center.read');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \Motor\Admin\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->hasPermissionTo('customer_center.write');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \Motor\Admin\Models\User  $user
     * @param  \Motor\Admin\Models\CustomerCenter  $customer_center
     * @return mixed
     */
    public function update(User $user, CustomerCenter $customer_center)
    {
        return $user->hasPermissionTo('customer_center.write');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \Motor\Admin\Models\User  $user
     * @param  \Motor\Admin\Models\CustomerCenter  $customer_center
     * @return mixed
     */
    public function delete(User $user, CustomerCenter $customer_center)
    {
        return $user->hasPermissionTo('customer_center.delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \Motor\Admin\Models\User  $user
     * @param  \Motor\Admin\Models\Role  $role
     * @return mixed
     */
    public function restore(User $user, Role $role)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \Motor\Admin\Models\User  $user
     * @param  \Motor\Admin\Models\Role  $role
     * @return mixed
     */
    public function forceDelete(User $user, Role $role)
    {
        //
    }
}
