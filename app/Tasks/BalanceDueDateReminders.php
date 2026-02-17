<?php

namespace App\Tasks;

use App\Models\Booking;
use App\Models\BookingClient;
use App\Notifications\BalanceDueDateReminder as BalanceDueDateReminderNotification;
use Illuminate\Support\Facades\DB;

class BalanceDueDateReminders 
{
    public function __invoke()
    {        
        $bookings = Booking::whereHas('activeGroup', function ($query) {
                $query->whereRaw(DB::raw('DATE(balance_due_date - INTERVAL 2 WEEK) = CAST(NOW() AS DATE)'));
                $query->where('disable_notifications', false);
            })
            ->orWhereRaw(DB::raw('DATE(balance_due_date - INTERVAL 2 WEEK) = CAST(NOW() AS DATE)'))
            ->get();

        $bookings->each(function($booking) {
            $booking->invoice->clients->each(function($client) {
                if ($client->total - $client->payments > 0) {
                    $bookingClient = BookingClient::where('reservation_code', $client->reservation_code)->first();

                    if (count($bookingClient->paymentArrangements) === 0) {
                        $guest = $bookingClient->guests->first();
                        $bookingClient->client->notify(new BalanceDueDateReminderNotification($bookingClient, $client, $guest->check_in));
                    }
                };
            });
        });
    }
}