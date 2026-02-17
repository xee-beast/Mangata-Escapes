<?php

namespace App\Policies;

use App\Models\Lead;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LeadPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function viewAny(User $user)
    {
        if ($user->hasPermissionTo('view all leads')) {
            return true;
        }

        return $user->hasPermissionTo('view own leads')
            && $user->travel_agent;
    }

    public function view(User $user, Lead $lead)
    {
        if ($user->hasPermissionTo('view all leads')) {
            return true;
        }

        return $user->hasPermissionTo('view own leads')
            && $user->travel_agent
            && $user->travel_agent->id === $lead->travel_agent_id;
    }

    public function create(User $user)
    {
        return $user->hasPermissionTo('create leads');
    }

    public function update(User $user, Lead $lead)
    {
        if ($user->hasPermissionTo('update all leads')) {
            return true;
        }

        return $user->hasPermissionTo('update own leads')
            && $user->travel_agent
            && $user->travel_agent->id === $lead->travel_agent_id;
    }

    public function delete(User $user, Lead $lead)
    {
        return $user->hasPermissionTo('delete leads');
    }
}
