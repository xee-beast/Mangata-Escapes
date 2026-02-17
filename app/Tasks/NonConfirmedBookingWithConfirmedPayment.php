<?php

namespace App\Tasks;

use App\Models\Booking;
use App\Notifications\NonConfirmedBookingWithConfirmedPayment as NonConfirmedBookingWithConfirmedPaymentNotification;

class NonConfirmedBookingWithConfirmedPayment
{
    public function __invoke()
    {
        $nonConfirmedBookingsWithConfirmedPayment = Booking::query()
            ->where(function ($query) {
                $query->whereHas('activeGroup', function ($query) {
                    $query->where('disable_notifications', false);
                })
                ->orWhereDoesntHave('group');
            })
            ->whereNull('confirmed_at')
            ->whereHas('payments', function ($query) {
                $query->whereDate('confirmed_at', today()->subWeek()->startOfDay());
            })->get();

        foreach ($nonConfirmedBookingsWithConfirmedPayment as $booking) {
            $booking->clients->each(function ($client) {
                $client->client->notify(new NonConfirmedBookingWithConfirmedPaymentNotification($client));
            });
        }
    }
}
