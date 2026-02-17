<?php

namespace App\Listeners;

use App\Events\BookingSubmitted;
use App\Notifications\BookingSubmitted as BookingSubmittedNotification;
use App\Notifications\BookingSubmittedReservationCodeSeperateInvoice as BookingSubmittedReservationCodeSeperateInvoiceNotification;

class SendBookingSubmittedNotification
{
    /**
     * Handle the event.
     *
     * @param  \App\Events\BookingSubmitted  $event
     * @return void
     */
    public function handle(BookingSubmitted $event)
    {
        $event->booking->clients->first()->client->notify(new BookingSubmittedNotification($event->booking));

        if ($event->booking->group && !$event->booking->group->is_fit) {
            $event->booking->clients->slice(1)->each(function ($client) use ($event) {
                $client->client->notify(new BookingSubmittedReservationCodeSeperateInvoiceNotification($event->booking));
            });
        }
    }
}
