<?php

namespace App\Policies;

use App\Models\Booking;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BookingPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any bookings.
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

        return $user->hasPermissionTo('manage bookings') || $user->hasPermissionTo('process bookings') || $allow;
    }

    /**
     * Determine whether the user can view the booking.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Booking  $booking
     * @return mixed
     */
    public function view(User $user, Booking $booking)
    {
        return $user->hasPermissionTo('manage bookings') || $user->hasPermissionTo('process bookings') || $user->can('view', $booking->group);
    }

    /**
     * Determine whether the user can create bookings.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->hasPermissionTo('manage bookings');
    }

    /**
     * Determine whether the user can update the booking.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Booking  $booking
     * @return mixed
     */
    public function update(User $user, Booking $booking)
    {
        if ($booking->trashed()) {
            return false;
        }

        return $user->hasPermissionTo('manage bookings');
    }

    /**
    * Determine whether the user can confirm the booking.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Booking  $booking
     * @return mixed
     */
    public function confirm(User $user, Booking $booking)
    {
        if (
            $booking->trashed() ||
            (!is_null($booking->confirmed_at)) ||
            ((!$booking->group || ($booking->group && $booking->group->is_fit)) && $booking->booking_clients()->whereDoesntHave('acceptedFitQuote')->exists())
        ) {
            return false;
        }

        return $user->hasPermissionTo('process bookings');
    }

    /**
     * Determine whether the user can confirm the changes made to this booking.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Booking  $booking
     * @return mixed
     */
    public function confirmChanges(User $user, Booking $booking)
    {
        if (! $booking->trackedChanges()->whereNull('confirmed_at')->exists()) {
            return false;
        }

        return $user->hasPermissionTo('manage bookings');
    }

    /**
     * Determine whether the user can delete the booking.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Booking  $booking
     * @return mixed
     */
    public function delete(User $user, Booking $booking)
    {
        if (
            $booking->trashed() ||
            ((!$booking->group || ($booking->group && $booking->group->is_fit)) && !$booking->booking_clients()->whereHas('fitQuotes')->exists()) ||
            (
                ($booking->group && !$booking->group->is_fit) &&
                is_null($booking->confirmed_at) &&
                !$booking->clients()->whereHas('payments', function ($query) { $query->where('confirmed_at', '!=', null); })->exists()
            )
        ) {
            return false;
        }

        return $user->hasPermissionTo('manage bookings');
    }

    /**
     * Determine whether the user can restore the booking.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Booking  $booking
     * @return mixed
     */
    public function restore(User $user, Booking $booking)
    {
        if (!$booking->trashed()) {
            return false;
        }

        return $user->hasPermissionTo('manage bookings');
    }

    /**
     * Determine whether the user can permanently delete the booking.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Booking  $booking
     * @return mixed
     */
    public function forceDelete(User $user, Booking $booking)
    {
        if (
            (!is_null($booking->confirmed_at)) ||
            ((!$booking->group || ($booking->group && $booking->group->is_fit)) && $booking->booking_clients()->whereHas('fitQuotes')->exists()) ||
            $booking->clients()->whereHas('payments', function($query) {
                $query->where('confirmed_at', '!=', null);
            })->exists()
        ) {
            return false;
        }

        return $user->hasPermissionTo('manage bookings');
    }
}
