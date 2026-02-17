<?php

namespace App\Policies;

use App\Models\Employee;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmployeePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any employees.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return $user->hasPermissionTo('view employees');
    }

    /**
     * Determine whether the user can view the employee.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Employee  $employee
     * @return mixed
     */
    public function view(User $user, Employee $employee)
    {
        if ($employee->user->isSuper() && !$user->isSuper()) {
            return false;
        }

        return $user->hasPermissionTo('view employees');
    }

    /**
     * Determine whether the user can create employees.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->hasPermissionTo('create employees');
    }

    /**
     * Determine whether the user can update the employee.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Employee  $employee
     * @return mixed
     */
    public function update(User $user, Employee $employee)
    {
        if ($employee->user->isAdmin() && !$user->isSuper() && ($employee->user->id != $user->id)) {
            return false;
        }

        return $user->hasPermissionTo('update employees');
    }

    /**
     * Determine whether the user can delete the employee.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Employee  $employee
     * @return mixed
     */
    public function delete(User $user, Employee $employee)
    {
        if ($employee->user->isSuper() || ($employee->user->isAdmin() && !$user->isSuper()) || ($user->id === $employee->user->id)) {
            return false;
        }

        return $user->hasPermissionTo('delete employees');
    }

    /**
     * Determine whether the user can manage an employee's roles.
     *
     * @param  \App\User  $user
     * @param  \App\Models\employee  $employee
     * @return mixed
     */
    public function syncRoles(User $user, Employee $employee)
    {
        if (!$employee->user->hasVerifiedEmail() || ($employee->user->isAdmin() && !$user->isSuper()) || $user->id === $employee->user->id) {
            return false;
        }

        return $user->hasPermissionTo('manage employee roles');
    }

    /**
     * Determine whether the user can manage an employee's permissions.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Employee  $employee
     * @return mixed
     */
    public function syncPermissions(User $user, Employee $employee)
    {
        if (!$employee->user->hasVerifiedEmail() || ($employee->user->isAdmin() && !$user->isSuper()) || $user->id === $employee->user->id) {
            return false;
        }

        return $user->hasPermissionTo('manage employee permissions');
    }

    /**
     * Determine whether the user can change an employees email.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Employee  $employee
     * @return mixed
     */
    public function changeEmail(User $user, Employee $employee)
    {
        return ($user->can('update', $employee) && !$employee->user->hasVerifiedEmail());
    }
}
