<?php

namespace App\Policies;

use App\Models\Hotel;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class HotelPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any hotels.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return $user->hasPermissionTo('view hotels');
    }

    /**
     * Determine whether the user can view the hotel.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Hotel  $hotel
     * @return mixed
     */
    public function view(User $user, Hotel $hotel)
    {
        return $user->hasPermissionTo('view hotels');
    }

    /**
     * Determine whether the user can create hotels.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->hasPermissionTo('create hotels');
    }

    /**
     * Determine whether the user can update the hotel.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Hotel  $hotel
     * @return mixed
     */
    public function update(User $user, Hotel $hotel)
    {
        return $user->hasPermissionTo('update hotels');
    }

    /**
     * Determine whether the user can delete the hotel.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Hotel  $hotel
     * @return mixed
     */
    public function delete(User $user, Hotel $hotel)
    {
        return $user->hasPermissionTo('delete hotels');
    }
}
