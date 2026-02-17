<?php

namespace App\Tasks;

use App\Models\BookingClient;
use App\Models\Guest;
use App\Notifications\FinalFlightManifestReminder;
use Illuminate\Support\Carbon;

class FinalFlightManifestReminders
{
    public function __invoke()
    {
        $bookingClients = BookingClient::with('booking.group')
            ->whereHas('booking', function ($query) {
                $query->whereHas('activeGroup', function ($query) {
                        $query->where('disable_notifications', false);
                        $query->where('transportation', true)
                            ->whereDate('transportation_submit_before', Carbon::yesterday());
                    })
                    ->orWhere(function ($query) {
                        $query->where('transportation', true)
                            ->whereDate('transportation_submit_before', Carbon::yesterday());
                    });
            })
            ->whereHas('guests', function ($query) {
                $query->where('transportation', true)
                    ->whereDoesntHave('flight_manifest');
            })
            ->get();

        $bookingClients->each(function ($bookingClient) {
            $guestsWithoutManifests =  Guest::where('transportation', true)->where('booking_client_id', $bookingClient->id)->whereDoesntHave('flight_manifest')->get();
            $notification = new FinalFlightManifestReminder($bookingClient, $guestsWithoutManifests);
            $bookingClient->client->notify($notification);
        });
    }
}
