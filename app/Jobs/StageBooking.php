<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Models\TrackedChange;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class StageBooking implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, Queueable;

    private $bookingId;

    private $trackable = [
        'special_requests',
        'clients' => [
            'extras' => [
                'booking_client_id',
                'description',
                'price',
                'quantity',
            ],
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
                'deleted_at',
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
    public function __construct($bookingId)
    {
        $this->bookingId = $bookingId;
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
                        'extras',
                        'guests' => function ($query) { $query->withTrashed(); }
                    ]);
                },
                'roomBlocks.room.hotel'
            ])
            ->firstOrFail();

        $snapshot = TrackedChange::snapshot($booking);

        if (is_null($booking->confirmed_at) || is_null($confirmed = $booking->trackedChanges()->whereNotNull('confirmed_at')->latest()->first())) {
            $booking->trackedChanges()->create([
                'snapshot' => $snapshot,
                'confirmed_at' => now(),
            ]);

            return;
        }

        if (TrackedChange::somethingChanged($this->trackable, $confirmed->snapshot, $snapshot)) {
            $booking->trackedChanges()->updateOrCreate(
                ['confirmed_at' => null],
                ['snapshot' => $snapshot]
            );

            return;
        }

        $booking->trackedChanges()->whereNull('confirmed_at')->delete();

        $confirmed->update([
            'snapshot' => $snapshot,
        ]);
    }
}
