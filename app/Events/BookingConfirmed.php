<?php

namespace App\Events;

use \App\Models\Booking;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingConfirmed
{
    use Dispatchable, SerializesModels;

    public $booking;
    public $sendEmail;

    /**
     * Create a new event instance.
     *
     * @param \App\Models\Booking $booking
     * @return void
     */
    public function __construct(Booking $booking, bool $sendEmail)
    {
        $this->booking = $booking;
        $this->sendEmail = $sendEmail;
    }
}
