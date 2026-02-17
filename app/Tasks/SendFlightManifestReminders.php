<?php

namespace App\Tasks;

use App\Models\BookingClient;
use App\Models\Guest;
use App\Notifications\FlightManifestRequest;
use Illuminate\Support\Carbon;

class SendFlightManifestReminders
{
    public function __invoke()
    {
        $bookingClients = BookingClient::with('booking.group')
            ->whereHas('booking', function ($query) {
                $query->whereHas('activeGroup', function ($query) {
                        $query->where('disable_notifications', false);
                        $query->where('transportation', true)
                            ->where(function ($q) {
                                $q->whereDate('transportation_submit_before', Carbon::today()->addDays(60))
                                    ->orWhereDate('transportation_submit_before', Carbon::today()->addDays(30))
                                    ->orWhereDate('transportation_submit_before', Carbon::today()->addDays(14))
                                    ->orWhereDate('transportation_submit_before', Carbon::today()->addDays(5))
                                    ->orWhereDate('transportation_submit_before', Carbon::today()->addDays(1));
                            });
                    })
                    ->orWhere(function ($query) {
                        $query->where('transportation', true)
                            ->where(function ($q) {
                                $q->whereDate('transportation_submit_before', Carbon::today()->addDays(60))
                                    ->orWhereDate('transportation_submit_before', Carbon::today()->addDays(30))
                                    ->orWhereDate('transportation_submit_before', Carbon::today()->addDays(14))
                                    ->orWhereDate('transportation_submit_before', Carbon::today()->addDays(5))
                                    ->orWhereDate('transportation_submit_before', Carbon::today()->addDays(1));
                            });
                    });
            })
            ->whereHas('guests', function ($query) {
                $query->where('transportation', true)
                    ->whereDoesntHave('flight_manifest');
            })
            ->get();

        $bookingClients->each(function ($bookingClient) {
            $guestsWithoutManifests =  Guest::where('transportation', true)->where('booking_client_id', $bookingClient->id)->whereDoesntHave('flight_manifest')->get();
            $notification = new FlightManifestRequest($bookingClient, $guestsWithoutManifests);
            $bookingClient->client->notify($notification);
        });
    }
}
