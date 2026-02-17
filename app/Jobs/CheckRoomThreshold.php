<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Models\BookingRoomBlock;
use App\Notifications\RoomThresholdNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class CheckRoomThreshold implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $booking;

    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
    }

    public function handle()
    {
        $group = $this->booking->group;
        $roomBlocks = $this->booking->roomBlocks->where('inventory', '>', 0);
        if ($roomBlocks->count() > 0) {
            foreach ($roomBlocks as $roomBlock) {
                $groupBookingIds = $group->bookings()->whereNull('deleted_at')->pluck('id');
                $bookingRoomBlockCount = BookingRoomBlock::whereIn('booking_id', $groupBookingIds)->where('room_block_id', $roomBlock->id)->count();
                $percentage = ($bookingRoomBlockCount / $roomBlock->inventory) * 100;

                if ($percentage >= 80) {
                    Notification::route('mail', config('emails.operations'))->notify(new RoomThresholdNotification($group, $roomBlock, $bookingRoomBlockCount, $percentage));
                }
            }
        }
    }
}
