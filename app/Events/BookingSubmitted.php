<?php

namespace App\Events;

use \App\Models\Booking;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingSubmitted
{
    use Dispatchable, SerializesModels;

    public $booking;

    /**
     * Create a new event instance.
     *
     * @param \App\Models\Booking $booking
     * @return void
     */
    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
    }
}
