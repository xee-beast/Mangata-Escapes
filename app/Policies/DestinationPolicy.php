<?php

namespace App\Policies;

use App\Models\Destination;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DestinationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any destinations.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return $user->hasPermissionTo('view destinations');
    }

    /**
     * Determine whether the user can view the destination.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Destination  $destination
     * @return mixed
     */
    public function view(User $user, Destination $destination)
    {
        return $user->hasPermissionTo('view destinations');
    }

    /**
     * Determine whether the user can create destinations.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->hasPermissionTo('create destinations');
    }

    /**
     * Determine whether the user can update the destination.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Destination  $destination
     * @return mixed
     */
    public function update(User $user, Destination $destination)
    {
        return $user->hasPermissionTo('update destinations');
    }

    /**
     * Determine whether the user can delete the destination.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Destination  $destination
     * @return mixed
     */
    public function delete(User $user, Destination $destination)
    {
        return ($user->hasPermissionTo('delete destinations') && (!$destination->hotels()->exists()));
    }
}
