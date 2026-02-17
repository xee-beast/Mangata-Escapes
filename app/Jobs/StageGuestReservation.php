<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Booking;
use App\Models\GuestChange;
use App\Events\GuestChangeSubmitted;

class StageGuestReservation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    private $bookingId;
    private $snapshot;
    private $bookingClientId;
    private $paymentData;

    private $trackable = [
        'special_requests',
        'clients' => [
            'guests' => [
                'booking_client_id',
                'first_name',
                'last_name',
                'gender',
                'birth_date',
                'check_in',
                'check_out',
                'insurance',
                'transportation',
                'transportation_type',
                'custom_group_airport',
            ],
        ],
        'roomBlocks' => [
            'pivot' => [
                'booking_id',
                'room_block_id',
                'bed',
                'check_in',
                'check_out',
            ],
        ],
    ];
    /**
     * The unique ID of the job.
     *
     * @return string
     */
    public function uniqueId()
    {
        return $this->bookingId;
    }

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($bookingClientId, $bookingId, $snapshot = null, $paymentData = null)
    {
        $this->bookingClientId = $bookingClientId;
        $this->bookingId = $bookingId;
        $this->snapshot = $snapshot;
        $this->paymentData = $paymentData;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $booking = Booking::withTrashed()
            ->where('id', $this->bookingId)
            ->with([
                'clients' => function ($query) {
                    $query->without('client')->with([
                        'guests' => function ($query) { $query->withTrashed(); }
                    ]);
                },
                'roomBlocks.room.hotel'
            ])
            ->firstOrFail();

        if ($this->snapshot) {
            $snapshot = $this->snapshot;
        } else {
            $snapshot = GuestChange::snapshot($booking);
        }

        $booking->guestChanges()->create([
            'booking_client_id' => $this->bookingClientId,
            'snapshot' => $snapshot,
            'payment_details' => json_encode($this->paymentData),
        ]);

        event(new GuestChangeSubmitted($this->bookingClientId));
    }
}
