<?php

namespace App\Events;

use App\Models\BookingClient;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GuestChangeCancelled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $bookingClient;

    public function __construct(BookingClient $bookingClient)
    {
        $this->bookingClient = $bookingClient;
    }
}
