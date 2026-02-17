<?php

namespace App\Listeners;

use App\Events\GuestChangeApproved;
use App\Notifications\GuestChangeApproved as GuestChangeApprovedNotification;

class SendGuestChangeApprovedNotification
{
    public function handle(GuestChangeApproved $event)
    {
        $bookingClient = $event->bookingClient;

        if ($bookingClient) {
            $bookingClient->client->notify(new GuestChangeApprovedNotification($bookingClient));
        }
    }
}
