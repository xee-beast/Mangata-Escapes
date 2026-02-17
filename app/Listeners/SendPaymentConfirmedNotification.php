<?php

namespace App\Listeners;

use App\Events\PaymentConfirmed;
use App\Notifications\BookingInvoiceFinal as BookingInvoiceFinalNotification;
use App\Notifications\PaymentConfirmed as PaymentConfirmedNotification;

class SendPaymentConfirmedNotification
{
    /**
     * Handle the event.
     *
     * @param  PaymentConfirmed  $event
     * @return void
     */
    public function handle(PaymentConfirmed $event)
    {
        if ($event->sendEmail && $event->payment->booking_client->booking->confirmed_at) {
            if ($event->payment->booking_client->booking->payment_total >= $event->payment->booking_client->booking->total) {
                $event->payment->booking_client->booking->clients->first()->client->notify(new BookingInvoiceFinalNotification($event->payment->booking_client));
            } else {
                $event->payment->booking_client->client->notify(new PaymentConfirmedNotification($event->payment));
            }
        }
    }
}
