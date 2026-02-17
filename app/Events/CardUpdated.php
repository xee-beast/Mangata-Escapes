<?php

namespace App\Events;

use App\Models\BookingClient;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CardUpdated
{
    use Dispatchable, SerializesModels;

    public $clientBooking;
    public $signedInsurance;

    /**
     * Create a new event instance.
     *
     * @param BookingClient $clientBooking
     * @param boolean $signedInsurance
     * @return void
     */
    public function __construct(BookingClient $clientBooking, $signedInsurance = false)
    {
        $this->clientBooking = $clientBooking;
        $this->signedInsurance = $signedInsurance;
    }
}
