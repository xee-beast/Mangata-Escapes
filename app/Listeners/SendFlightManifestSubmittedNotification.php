<?php

namespace App\Listeners;

use App\Events\FlightManifestSubmitted;
use App\Notifications\FlightManifestSubmitted as FlightManifestSubmittedNotification;

class SendFlightManifestSubmittedNotification
{
    /**
     * Handle the event.
     *
     * @param  \App\Events\FlightManifestSubmitted  $event
     * @return void
     */
    public function handle(FlightManifestSubmitted $event)
    {
        $event->bookingClient->client->notify(new FlightManifestSubmittedNotification($event->bookingClient));
    }
}
