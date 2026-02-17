<?php

namespace App\Tasks;

use App\Models\BookingClient;
use App\Notifications\CancellationsLastCalls as CancellationsLastCallNotification;
use Illuminate\Support\Facades\DB;

class CancellationsLastCalls
{
    public function __invoke()
    {
        $bookingClients = BookingClient::with('booking.group')
            ->whereHas('booking', function ($query) {
                $query->whereHas('activeGroup', function ($query) {
                        $query->whereRaw(DB::raw('DATE(cancellation_date - INTERVAL 2 WEEK) = CAST(NOW() AS DATE)'));
                        $query->where('disable_notifications', false);
                    })
                    ->orWhereRaw(DB::raw('DATE(cancellation_date - INTERVAL 2 WEEK) = CAST(NOW() AS DATE)'));
            })
            ->get();
         
        $bookingClients->each(function ($bookingClient) {
            $bookingClient->client->notify(new CancellationsLastCallNotification($bookingClient));
        });
    }
}