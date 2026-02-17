<?php

namespace App\Listeners;

use App\Events\BookingSubmitted;
use App\Models\Guest;
use App\Notifications\FlightManifestRequest;
use Illuminate\Support\Carbon;

class SendFlightManifestRequestNotification
{
    /**
     * Handle the event.
     *
     * @param  \App\Events\BookingSubmitted  $event
     * @return void
     */
    public function handle(BookingSubmitted $event)
    {        
        if (
            $event->booking->transportedGuests->count() > 0 &&
            $event->booking->group &&
            !$event->booking->group->is_fit &&
            $event->booking->group->transportation &&
            (! is_null($event->booking->group->transportation_submit_before)) &&
            $event->booking->group->transportation_submit_before->between(Carbon::now(), Carbon::now()->addMonth())
        ) {
            $event->booking->clients->each(function ($bookingClient) {
                $guestsWithoutManifests =  Guest::where('transportation', true)->where('booking_client_id', $bookingClient->id)->whereDoesntHave('flight_manifest')->get();
                $bookingClient->client->notify(new FlightManifestRequest($bookingClient, $guestsWithoutManifests));
            });
        }        
    }
}
