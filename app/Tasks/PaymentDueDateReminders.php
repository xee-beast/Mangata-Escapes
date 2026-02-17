<?php

namespace App\Tasks;

use App\Models\BookingDueDate;
use App\Models\DueDate;
use App\Models\BookingClient;
use App\Notifications\PaymentDueDateReminder as PaymentDueDateReminderNotification;
use Illuminate\Support\Facades\DB;

class PaymentDueDateReminders
{
    public function __invoke()
    {
        $dueDates = DueDate::where(function ($query) {
            $query->whereHas('group', function ($query) {
                $query->where('disable_notifications', false);
            })
            ->orWhereDoesntHave('group');
        })->whereRaw(DB::raw('DATE(date - INTERVAL 2 WEEK) = CAST(NOW() AS DATE)'))->get();

        $bookingDueDates =  BookingDueDate::where(function ($query) {
            $query->whereHas('booking', function ($query) {
                $query->whereHas('activeGroup', function ($query) {
                    $query->where('disable_notifications', false);
                });
            })
            ->orWhereDoesntHave('booking.group');
        })->whereRaw(DB::raw('DATE(date - INTERVAL 2 WEEK) = CAST(NOW() AS DATE)'))->get();

        $dueDates->each(function ($dueDate) {
            $dueDate->group->bookings->each(function ($booking) use ($dueDate) {
                $this->sendReminder($booking, $dueDate);
            });
        });

        $bookingDueDates->each(function ($bookingDueDate) {
            $this->sendReminder($bookingDueDate->booking, $bookingDueDate);
        });
    }

    public function sendReminder($booking, $dueDate) {
        $booking->invoice->clients->each(function($client) use ($dueDate) {
            $amount = 0;                    

            if ($dueDate->type == 'price') {
                $amount = $dueDate->amount;
            } else if ($dueDate->type == 'percentage') {
                $amount = $client->total * ($dueDate->amount / 100);
            } else if ($dueDate->type == 'nights') {
                $nights = $dueDate->amount;

                $client->guests->each(function ($guest) use (&$amount, $nights) {
                    $nightsLeft = $nights;

                    $guest->items->each(function ($item) use (&$amount, $nights, &$nightsLeft) {
                        $item->each(function ($item) use (&$amount, $nights, &$nightsLeft) {
                            if ($nightsLeft > 0) {
                                if ($item->quantity > $nightsLeft) {
                                    $amount += $item->rate * $nightsLeft;
                                } else {
                                    $amount += $item->rate * $item->quantity;
                                }
                                
                                $nightsLeft -= $item->quantity;
                            }
                        });
                    });
                });

                $client->insurance->each(function ($insurance) use (&$amount) {
                    $amount += $insurance->rate * $insurance->quantity;
                });
            }

            if ($amount > $client->payments) {
                $bookingClient = BookingClient::where('reservation_code', $client->reservation_code)->first();

                if (count($bookingClient->paymentArrangements) === 0) {
                    $params = (object) [
                        'reservation_code' => $client->reservation_code,
                        'amount' => $amount - $client->payments
                    ];

                    $bookingClient->client->notify(new PaymentDueDateReminderNotification($params, $dueDate));
                }
            }
        });
    }
}