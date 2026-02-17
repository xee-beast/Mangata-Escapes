<?php

namespace App\Listeners;

use App\Events\PaymentSubmitted;
use App\Notifications\PaymentSubmitted as PaymentSubmittedNotification;

class SendPaymentSubmittedNotification
{
    /**
     * Handle the event.
     *
     * @param  PaymentSubmitted  $event
     * @return void
     */
    public function handle(PaymentSubmitted $event)
    {
        $event->payment->booking_client->client->notify(new PaymentSubmittedNotification($event->payment));
    }
}
