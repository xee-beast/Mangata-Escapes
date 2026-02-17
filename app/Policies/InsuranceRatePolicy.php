<?php

namespace App\Policies;

use App\Models\InsuranceRate;
use App\Models\Provider;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InsuranceRatePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any insurance rates.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        $allow = false;
        if (!is_null($provider = request()->route('provider'))) {
            $allow = $user->can('view', $provider);
        }

        return $user->hasPermissionTo('manage insurance rates') || $allow;
    }

    /**
     * Determine whether the user can view the insurance rate.
     *
     * @param  \App\User  $user
     * @param  \App\Models\InsuranceRate  $insuranceRate
     * @return mixed
     */
    public function view(User $user, InsuranceRate $insuranceRate)
    {
        $allow = false;
        if (!is_null($provider = request()->route('provider'))) {
            $allow = $user->can('view', $provider);
        }

        return $user->hasPermissionTo('manage insurance rates') || $allow;
    }

    /**
     * Determine whether the user can create insurance rates.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->hasPermissionTo('manage insurance rates');
    }

    /**
     * Determine whether the user can update the insurance rate.
     *
     * @param  \App\User  $user
     * @param  \App\Models\InsuranceRate  $insuranceRate
     * @return mixed
     */
    public function update(User $user, InsuranceRate $insuranceRate, Provider $provider = null)
    {    
        return $user->hasPermissionTo('manage insurance rates');
    }

    /**
     * Determine whether the user can delete the insurance rate.
     *
     * @param  \App\User  $user
     * @param  \App\Models\InsuranceRate  $insuranceRate
     * @return mixed
     */
    public function delete(User $user, InsuranceRate $insuranceRate)
    {
        return $user->hasPermissionTo('manage insurance rates') && !$insuranceRate->groups()->exists();
    }
}
