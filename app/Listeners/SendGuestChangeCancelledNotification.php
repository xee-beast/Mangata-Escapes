<?php

namespace App\Listeners;

use App\Events\GuestChangeCancelled;
use App\Notifications\GuestChangeCancelled as GuestChangeCancelledNotification;

class SendGuestChangeCancelledNotification
{
    public function handle(GuestChangeCancelled $event)
    {
        $bookingClient = $event->bookingClient;

        if ($bookingClient) {
            $bookingClient->client->notify(new GuestChangeCancelledNotification($bookingClient));
        }
    }
}
