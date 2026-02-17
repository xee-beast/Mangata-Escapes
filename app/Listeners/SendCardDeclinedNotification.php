<?php

namespace App\Listeners;

use App\Events\CardDeclined;
use App\Notifications\CardDeclined as CardDeclinedNotification;

class SendCardDeclinedNotification
{

    /**
     * Handle the event.
     *
     * @param  \App\Events\CardDeclined  $event
     * @return void
     */
    public function handle(CardDeclined $event)
    {
        $event->payment->booking_client->client->notify(new CardDeclinedNotification($event->payment));
    }
}
