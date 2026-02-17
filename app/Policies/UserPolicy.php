<?php

namespace App\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return $user->hasPermissionTo('view users');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\User  $user
     * @param  \App\User  $model
     * @return mixed
     */
    public function view(User $user, User $model)
    {
        if ($model->isSuper() && !$user->isSuper()) {
            return false;
        }

        return $user->hasPermissionTo('view users');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->hasPermissionTo('create users');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\User  $user
     * @param  \App\User  $model
     * @return mixed
     */
    public function update(User $user, User $model)
    {
        if ($model->isAdmin() && !$user->isSuper() && ($model->id != $user->id)) {
            return false;
        }

        return $user->hasPermissionTo('update users');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\User  $user
     * @param  \App\User  $model
     * @return mixed
     */
    public function delete(User $user, User $model)
    {
        if (
            $model->isSuper() ||
            ($model->isAdmin() && !$user->isSuper()) ||
            ($user->id === $model->id) 
            // || $model->travel_agent()->whereHas('groups')->exists()
        ) {
            return false;
        }

        return $user->hasPermissionTo('delete users');
    }

    /**
     * Determine whether the user can manage a model's roles.
     *
     * @param  \App\User  $user
     * @param  \App\User  $model
     * @return mixed
     */
    public function syncRoles(User $user, User $model)
    {
        if (
            !$model->hasVerifiedEmail() ||
            ($model->isAdmin() && !$user->isSuper()) ||
            $user->id === $model->id
        ) {
            return false;
        }

        return $user->hasPermissionTo('manage user roles');
    }

    /**
     * Determine whether the user can manage a model's permissions.
     *
     * @param  \App\User  $user
     * @param  \App\User  $model
     * @return mixed
     */
    public function syncPermissions(User $user, User $model)
    {
        if (
            !$model->hasVerifiedEmail()
            || ($model->isAdmin() && !$user->isSuper()) ||
            $user->id === $model->id
        ) {
            return false;
        }

        return $user->hasPermissionTo('manage user permissions');
    }

    /**
     * Determine whether the user can upload files.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function uploadFiles(User $user)
    {
        return true;
    }
}
