<?php

namespace App\Events;

use App\Models\BookingClient;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FlightManifestSubmitted
{
    use Dispatchable, SerializesModels;

    public $bookingClient;
    public $dates_mismatch;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(BookingClient $bookingClient, $dates_mismatch)
    {
        $this->bookingClient = $bookingClient;
        $this->dates_mismatch = $dates_mismatch;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
