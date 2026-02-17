<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\BookingClient;
use App\Models\BookingDueDate;
use App\Models\BookingPaymentDate;
use App\Models\DueDate;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CreatePayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will create payments based on specified dates.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $dueDates =  DueDate::whereHas('group')->whereDate('date', Carbon::today('America/New_York'))->get();
        $bookingDueDates =  BookingDueDate::whereHas('booking')->whereDate('date', Carbon::today('America/New_York'))->get();

        $paymentArrangements = BookingPaymentDate::whereHas('booking')->whereDate('due_date', Carbon::today('America/New_York'))->get();

        $bookings = Booking::whereHas('activeGroup', function ($query) {
                $query->whereDate('balance_due_date', Carbon::today('America/New_York'));
            })
            ->orWhereDate('balance_due_date', Carbon::today('America/New_York'))
            ->get();

        foreach ($paymentArrangements as $paymentArrangement) {
            $bookingClient = $paymentArrangement->bookingClient;

            if ($bookingClient && $bookingClient->card) {
                $bookingClient->payments()->create([
                    'card_id' => $bookingClient->card->id,
                    'amount' => $paymentArrangement->amount,
                    'notes' => 'System Generated',
                ]);
            }
        }

        $bookings->each(function($booking) {
            $booking->invoice->clients->each(function($client) use ($booking) {
                $bookingClient = BookingClient::where('reservation_code', $client->reservation_code)->first();
                
                if($client->total - $client->paymentsWithPending > 0 && !$booking->paymentArrangements()->where('booking_client_id', $bookingClient->id)->exists()) {
                    if ($bookingClient && $bookingClient->card) {
                        $bookingClient->payments()->create([
                            'card_id' => $bookingClient->card->id,
                            'amount' => $client->total - $client->paymentsWithPending,
                            'notes' => 'System Generated',
                        ]);
                    }
                };
            });
        });

        $dueDates->each(function ($dueDate) use ($bookings) {
            $dueDate->group->bookings->each(function ($booking) use ($dueDate, $bookings) {
                if (!$bookings->contains($booking)) {
                    $booking->invoice->clients->each(function($client) use ($dueDate, $booking) {
                        $bookingClient = BookingClient::where('reservation_code', $client->reservation_code)->first();

                        if (!$booking->paymentArrangements()->where('booking_client_id', $bookingClient->id)->exists()) {
                            $amount = 0;

                            if($dueDate->type == 'percentage') {
                                $amount = $client->total * ($dueDate->amount / 100);
                            } else if($dueDate->type == 'nights') {
                                $nights = $dueDate->amount;

                                $client->guests->each(function ($guest) use (&$amount, $nights) {
                                    $nightsLeft = $nights;

                                    $guest->items->each(function ($item) use (&$amount, $nights, &$nightsLeft) {
                                        $item->each(function ($item) use (&$amount, $nights, &$nightsLeft) {
                                            if($nightsLeft > 0) {
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
                            } else if ($dueDate->type == 'price') {
                                $amount = $dueDate->amount;
                            }

                            if($amount > $client->paymentsWithPending) {
                                if ($bookingClient && $bookingClient->card) {
                                    $bookingClient->payments()->create([
                                        'card_id' => $bookingClient->card->id,
                                        'amount' => $amount - $client->paymentsWithPending,
                                        'notes' => 'System Generated',
                                    ]);
                                }
                            }
                        }
                    });
                }
            });
        });

        $bookingDueDates->each(function ($bookingDueDate) use ($bookings) {
            $booking = $bookingDueDate->booking;

            if (!$bookings->contains($booking)) {
                $booking->invoice->clients->each(function($client) use ($bookingDueDate, $booking) {
                    $bookingClient = BookingClient::where('reservation_code', $client->reservation_code)->first();

                    if (!$booking->paymentArrangements()->where('booking_client_id', $bookingClient->id)->exists()) {
                        $amount = 0;

                        if($bookingDueDate->type == 'percentage') {
                            $amount = $client->total * ($bookingDueDate->amount / 100);
                        } else if($bookingDueDate->type == 'nights') {
                            $nights = $bookingDueDate->amount;

                            $client->guests->each(function ($guest) use (&$amount, $nights) {
                                $nightsLeft = $nights;

                                $guest->items->each(function ($item) use (&$amount, $nights, &$nightsLeft) {
                                    $item->each(function ($item) use (&$amount, $nights, &$nightsLeft) {
                                        if($nightsLeft > 0) {
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
                        } else if ($bookingDueDate->type == 'price') {
                            $amount = $bookingDueDate->amount;
                        }

                        if($amount > $client->paymentsWithPending) {
                            if ($bookingClient && $bookingClient->card) {
                                $bookingClient->payments()->create([
                                    'card_id' => $bookingClient->card->id,
                                    'amount' => $amount - $client->paymentsWithPending,
                                    'notes' => 'System Generated',
                                ]);
                            }
                        }
                    }
                });
            }
        });
    }
}
