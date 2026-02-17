<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\BookingClient;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BookingClientPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any booking clients.
     *
     * @param  \App\User  $user
     * @param \App\Models\Booking $booking
     * @return mixed
     */
    public function viewAny(User $user)
    {
        $allow = false;
        if (!is_null($booking = request()->route('booking'))) {
            $allow = $user->can('view', $booking);
        }

        return $allow || $user->hasPermissionTo('manage clients');
    }

    /**
     * Determine whether the user can view the booking client.
     *
     * @param  \App\User  $user
     * @param  \App\Models\BookingClient  $bookingClient
     * @param \App\Models\Booking $booking
     * @return mixed
     */
    public function view(User $user, BookingClient $bookingClient)
    {
        $allow = false;
        if (!is_null($booking = request()->route('booking'))) {
            $allow = $user->can('view', $booking);
        }

        return $allow || $user->hasPermissionTo('manage clients');
    }

    /**
     * Determine whether the user can create booking clients.
     *
     * @param  \App\User  $user
     * @param \App\Models\Booking $booking
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->hasPermissionTo('manage clients');
    }

    /**
     * Determine whether the user can update the booking client.
     *
     * @param  \App\User  $user
     * @param  \App\Models\BookingClient  $bookingClient
     * @param \App\Models\Booking $booking
     * @return mixed
     */
    public function update(User $user, BookingClient $bookingClient)
    {
        return $user->hasPermissionTo('manage clients');
    }

    /**
     * Determine whether the user can delete the booking client.
     *
     * @param  \App\User  $user
     * @param  \App\Models\BookingClient  $bookingClient
     * @param \App\Models\Booking $booking
     * @return mixed
     */
    public function delete(User $user, BookingClient $bookingClient)
    {
        if ($bookingClient->guests()->exists() || $bookingClient->payments()->whereNotNull('confirmed_at')->sum('amount') > 0) {
            return false;
        }

        return $user->hasPermissionTo('manage clients');
    }
}
