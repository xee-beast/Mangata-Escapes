<?php

namespace App\Listeners;

use App\Events\BookingConfirmed;
use App\Notifications\BookingInvoice as BookingInvoiceNotification;
use App\Notifications\BookingInvoiceFinal as BookingInvoiceFinalNotification;

class SendBookingInvoiceNotification
{
    /**
     * Handle the event.
     *
     * @param  BookingConfirmed  $event
     * @return void
     */
    public function handle(BookingConfirmed $event)
    {
        if ($event->booking->payment_total >= $event->booking->total) {
            $event->booking->clients->each(function ($bookingClient) {
                $bookingClient->client->notify(new BookingInvoiceFinalNotification($bookingClient));
            });
        } else {
            if ($event->sendEmail) {
                $event->booking->clients->each(function ($bookingClient) {
                    $bookingClient->client->notify(new BookingInvoiceNotification($bookingClient));
                });
            }
        }
    }
}
