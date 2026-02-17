<?php

namespace App\Observers;

use App\Models\BookingClient;

class BookingClientObserver
{
    /**
     * Handle the booking client "creating" event.
     *
     * @param  \App\Models\BookingClient  $bookingClient
     * @return void
     */
    public function creating(BookingClient $bookingClient)
    {
        $alpha = 'ABCDEFGHJKLMNPRSTUVWXYZ';
        $numeric= '0123456789';

        do {
            $code = '';

            for ($i = 0; $i < 3; $i++) {
                $code .= $alpha[rand(0, 22)];
            }
            for ($i = 0; $i < 3; $i++) {
                $code .= $numeric[rand(0, 9)];
            }

            $code = str_shuffle($code);
        } while (BookingClient::where('reservation_code', $code)->exists());

        $bookingClient->reservation_code = $code;
    }

    /**
     * Handle the booking client "saved" event.
     *
     * @param  \App\Models\BookingClient  $bookingClient
     * @return void
     */
    public function saved(BookingClient $bookingClient)
    {
        if(!empty($bookingClient->booking)) $bookingClient->booking->touch();
    }

    /**
     * Handle the booking client "deleted" event.
     *
     * @param  \App\Models\BookingClient  $bookingClient
     * @return void
     */
    public function deleted(BookingClient $bookingClient)
    {
        $bookingClient->booking->touch();
    }
}
