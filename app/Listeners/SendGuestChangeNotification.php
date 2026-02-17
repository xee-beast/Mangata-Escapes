<?php

namespace App\Listeners;

use App\Events\GuestChangeSubmitted;
use App\Notifications\GuestChangeSubmitted as GuestChangeNotification;
use Illuminate\Support\Facades\Notification;
use App\Models\BookingClient;

class SendGuestChangeNotification
{
    public function handle(GuestChangeSubmitted $event)
    {
        $bookingClient = BookingClient::with(['booking.group'])->find($event->bookingClientId);

        if ($bookingClient) {
            Notification::route('mail', config('emails.operations'))->notify(new GuestChangeNotification($bookingClient));
        }
    }
}
