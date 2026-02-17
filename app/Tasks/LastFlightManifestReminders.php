<?php

namespace App\Tasks;

use App\Models\BookingClient;
use App\Models\Guest;
use App\Notifications\LastFlightManifestReminder;
use Illuminate\Support\Carbon;

class LastFlightManifestReminders
{
    public function __invoke()
    {
        $bookingClients = BookingClient::with('booking.group')
            ->whereHas('booking', function ($query) {
                $query->whereHas('activeGroup', function ($query) {
                        $query->where('disable_notifications', false);
                        $query->where('transportation', true)
                            ->whereDate('event_date', Carbon::today()->addDays(30));
                    })
                    ->orWhere(function ($query) {
                        $query->where('transportation', true)
                            ->whereDate('check_in', Carbon::today()->addDays(30));
                    });
            })
            ->whereHas('guests', function ($query) {
                $query->where('transportation', true)
                    ->whereDoesntHave('flight_manifest');
            })
            ->get();

        $bookingClients->each(function ($bookingClient) {
            $guestsWithoutManifests =  Guest::where('transportation', true)->where('booking_client_id', $bookingClient->id)->whereDoesntHave('flight_manifest')->get();
            $notification = new LastFlightManifestReminder($bookingClient, $guestsWithoutManifests);
            $bookingClient->client->notify($notification);
        });
    }
}
