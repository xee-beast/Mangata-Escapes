<?php

namespace App\Policies;

use App\Models\Payment;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PaymentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any payments.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        $allow = false;

        if (!is_null($group = request()->route('group'))) {
            $allow = $user->can('view', $group);
        }

        if (!is_null($booking = request()->route('booking'))) {
            $allow = $user->can('view', $booking);
        }

        return $user->hasPermissionTo('process payments') || $allow;
    }

    /**
     * Determine whether the user can view the payment.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Payment  $payment
     * @return mixed
     */
    public function view(User $user, Payment $payment)
    {
        $allow = false;

        if (!is_null($group = request()->route('group'))) {
            $allow = $user->can('view', $group);
        }

        if (!is_null($booking = request()->route('booking'))) {
            $allow = $user->can('view', $booking);
        }

        return $user->hasPermissionTo('process payments') || $allow;
    }

    /**
     * Determine whether the user can create payments.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->hasPermissionTo('create payments');
    }

    /**
     * Determine whether the user can update the payment.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Payment  $payment
     * @return mixed
     */
    public function update(User $user, Payment $payment)
    {
        return $user->hasPermissionTo('process payments') && $user->hasPermissionTo('update payments');
    }

    /**
     * Determine whether the user can delete the payment.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Payment  $payment
     * @return mixed
     */
    public function delete(User $user, Payment $payment)
    {
        if (
            !is_null($payment->cancelled_at) ||
            !is_null($payment->confirmed_at)
        ) {
            return false;
        }

        return $user->hasPermissionTo('process payments');
    }

    public function forceDelete(User $user, Payment $payment)
    {
        return $user->hasPermissionTo('process payments') && $user->hasPermissionTo('delete payments');
    }

    /**
     * Determine whether the user can confirm the payment.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Payment  $payment
     * @return mixed
     */
    public function confirm(User $user, Payment $payment)
    {
        if (
            !is_null($payment->confirmed_at) ||
            !is_null($payment->cancelled_at)
        ) {
            return false;
        }

        return $user->hasPermissionTo('process payments');
    }

    /**
     * Determine whether the user can process the payment.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function processPayments(User $user)
    {
        return $user->hasPermissionTo('process payments');
    }
}
