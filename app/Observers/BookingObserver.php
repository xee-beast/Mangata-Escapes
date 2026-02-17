<?php

namespace App\Observers;

use App\Jobs\StageBooking;
use App\Jobs\CheckRoomThreshold;
use App\Models\Booking;
use App\Models\Image;

class BookingObserver
{
    /**
     * Handle the Booking "saved" event.
     *
     * @param  \App\Models\Booking  $booking
     * @return void
     */
    public function saved(Booking $booking)
    {
        if ($booking->group) {
            StageBooking::dispatch($booking->id)->delay(now()->addSeconds(15));
        }
    }

    public function created(Booking $booking)
    {
        if ($booking->group) {
            CheckRoomThreshold::dispatch($booking)->delay(now()->addSeconds(5));
        }
    }

    public function updated(Booking $booking)
    {
        $oldTravelDocsCoverImage = $booking->getOriginal('travel_docs_cover_image_id');

        if ($booking->wasChanged('travel_docs_cover_image_id') && !is_null($oldTravelDocsCoverImage)) {
            Image::find($oldTravelDocsCoverImage)->delete();
        }

        $oldTravelDocsImageTwo = $booking->getOriginal('travel_docs_image_two_id');

        if ($booking->wasChanged('travel_docs_image_two_id') && !is_null($oldTravelDocsImageTwo)) {
            Image::find($oldTravelDocsImageTwo)->delete();
        }

        $oldTravelDocsImageThree = $booking->getOriginal('travel_docs_image_three_id');

        if ($booking->wasChanged('travel_docs_image_three_id') && !is_null($oldTravelDocsImageThree)) {
            Image::find($oldTravelDocsImageThree)->delete();
        }
    }

    /**
     * Handle the Booking "deleted" event.
     *
     * @param  \App\Models\Booking  $booking
     * @return void
     */
    public function deleted(Booking $booking)
    {
        if ($booking->group) {
            StageBooking::dispatch($booking->id)->delay(now()->addSeconds(15));
        }
    }
}
