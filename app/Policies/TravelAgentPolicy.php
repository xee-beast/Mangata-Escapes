<?php

namespace App\Policies;

use App\Models\TravelAgent;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TravelAgentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any travel agents.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return $user->hasPermissionTo('view travel agents');
    }

    /**
     * Determine whether the user can view the travel agent.
     *
     * @param  \App\User  $user
     * @param  \App\Models\TravelAgent  $travelAgent
     * @return mixed
     */
    public function view(User $user, TravelAgent $travelAgent)
    {
        return $user->hasPermissionTo('view travel agents');
    }

    /**
     * Determine whether the user can create travel agents.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->hasPermissionTo('create travel agents');

    }

    /**
     * Determine whether the user can update the travel agent.
     *
     * @param  \App\User  $user
     * @param  \App\Models\TravelAgent  $travelAgent
     * @return mixed
     */
    public function update(User $user, TravelAgent $travelAgent)
    {
        return $user->hasPermissionTo('update travel agents');
    }

    /**
     * Determine whether the user can delete the travel agent.
     *
     * @param  \App\User  $user
     * @param  \App\Models\TravelAgent  $travelAgent
     * @return mixed
     */
    public function delete(User $user, TravelAgent $travelAgent)
    {
        if($travelAgent->groups()->exists()) {
            return false;
        }

        return $user->hasPermissionTo('delete travel agents');
    }
}
