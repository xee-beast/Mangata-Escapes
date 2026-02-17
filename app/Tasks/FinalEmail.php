<?php

namespace App\Tasks;

use App\Models\BookingClient;
use App\Notifications\FinalEmail as FinalEmailNotification;
use Illuminate\Support\Facades\DB;

class FinalEmail
{
    public function __invoke()
    {
        $bookingClients = BookingClient::whereHas('guests.flight_manifest', function ($query) {
                $query->whereRaw(DB::raw('DATE(arrival_departure_date - INTERVAL 7 DAY) = CAST(NOW() AS DATE)'));
            })
            ->where(function ($query) {
                    $query->whereHas('booking.activeGroup', function ($query) {
                        $query->where('disable_notifications', false);
                    })
                    ->orWhereDoesntHave('booking.group');
            })
            ->get();

        $bookingClients->each(function ($bookingClient) {
            $bookingClient->client->notify(new FinalEmailNotification($bookingClient));
        });
    }
}