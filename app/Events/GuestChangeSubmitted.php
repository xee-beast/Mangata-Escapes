<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GuestChangeSubmitted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $bookingClientId;

    public function __construct($bookingClientId)
    {
        $this->bookingClientId = $bookingClientId;
    }
}
