<?php

namespace Motor\Admin\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Motor\Admin\Models\User;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     *
     * @param \Motor\Admin\Models\User $user
     * @param string $ability
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
     * @param \Motor\Admin\Models\User $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param \Motor\Admin\Models\User $user
     * @param \Motor\Admin\Models\User $model
     * @return mixed
     */
    public function view(User $user, User $model)
    {
        return $user->id === $model->id || $user->hasPermissionTo('user.read');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param \Motor\Admin\Models\User $user
     * @return mixed
     */
    public function create(User $user)
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param \Motor\Admin\Models\User $user
     * @param \Motor\Admin\Models\User $model
     * @return mixed
     */
    public function update(User $user, User $model)
    {
        return $user->id === $model->id || $user->hasPermissionTo('user.write');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param \Motor\Admin\Models\User $user
     * @param \Motor\Admin\Models\User $model
     * @return mixed
     */
    public function delete(User $user, User $model)
    {
        return $user->hasPermissionTo('user.delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param \Motor\Admin\Models\User $user
     * @param \Motor\Admin\Models\User $model
     * @return mixed
     */
    public function restore(User $user, User $model)
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param \Motor\Admin\Models\User $user
     * @param \Motor\Admin\Models\User $model
     * @return mixed
     */
    public function forceDelete(User $user, User $model)
    {
        return false;
    }
}