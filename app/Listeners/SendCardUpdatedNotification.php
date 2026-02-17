<?php

namespace App\Listeners;

use App\Events\CardUpdated;
use App\Notifications\PaymentInformationUpdatedNotification;

class SendCardUpdatedNotification
{

    /**
     * Handle the event.
     *
     * @param  \App\Events\CardUpdated  $event
     * @return void
     */
    public function handle(CardUpdated $event)
    {
        $event->clientBooking->client->notify(new PaymentInformationUpdatedNotification($event->clientBooking));
    }
}
