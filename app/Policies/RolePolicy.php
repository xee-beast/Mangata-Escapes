<?php

namespace App\Policies;

use App\Http\Controllers\Traits\ForbiddenRolesPermissions;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    use HandlesAuthorization, ForbiddenRolesPermissions;

    /**
     * Determine whether the user can view any roles.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return $user->hasPermissionTo('manage roles');
    }

    /**
     * Determine whether the user can view the role.
     *
     * @param  \App\User  $user
     * @param  \App\Role  $role
     * @return mixed
     */
    public function view(User $user, Role $role)
    {
        if(in_array($role->name, $this->forbiddenRoles())) {
            return false;
        }

        return $user->hasPermissionTo('manage roles');
    }

    /**
     * Determine whether the user can create roles.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->hasPermissionTo('manage roles');
    }

    /**
     * Determine whether the user can update the role.
     *
     * @param  \App\User  $user
     * @param  \App\Role  $role
     * @return mixed
     */
    public function update(User $user, Role $role)
    {
        if ($role->name == 'super admin' || $role->name == 'admin') {
            return false;
        }

        return $user->hasPermissionTo('manage roles');
    }

    /**
     * Determine whether the user can delete the role.
     *
     * @param  \App\User  $user
     * @param  \App\Role  $role
     * @return mixed
     */
    public function delete(User $user, Role $role)
    {
        if ($role->name == 'super admin' || $role->name == 'admin') {
            return false;
        }

        return $user->hasPermissionTo('manage roles');
    }
}
