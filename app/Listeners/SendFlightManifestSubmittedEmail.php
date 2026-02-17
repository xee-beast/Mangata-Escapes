<?php

namespace App\Listeners;

use App\Events\FlightManifestSubmitted;
use App\Mail\NewFlightManifest as FlightManifest;
use Illuminate\Support\Facades\Mail;

class SendFlightManifestSubmittedEmail
{
    /**
     * Handle the event.
     *
     * @param  \App\Events\FlightManifestSubmitted  $event
     * @return void
     */
    public function handle(FlightManifestSubmitted $event)
    {
        Mail::to(config('emails.operations'))->send(new FlightManifest('operations', $event->bookingClient, $event->dates_mismatch));
    }
}
