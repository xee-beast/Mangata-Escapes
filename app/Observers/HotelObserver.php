<?php

namespace App\Observers;

use App\Models\Hotel;
use App\Models\Image;

class HotelObserver
{
    public function updated(Hotel $hotel)
    {
        $oldTravelDocsCoverImage = $hotel->getOriginal('travel_docs_cover_image_id');

        if ($hotel->wasChanged('travel_docs_cover_image_id') && !is_null($oldTravelDocsCoverImage)) {
            Image::find($oldTravelDocsCoverImage)->delete();
        }

        $oldTravelDocsImageTwo = $hotel->getOriginal('travel_docs_image_two_id');

        if ($hotel->wasChanged('travel_docs_image_two_id') && !is_null($oldTravelDocsImageTwo)) {
            Image::find($oldTravelDocsImageTwo)->delete();
        }

        $oldTravelDocsImageThree = $hotel->getOriginal('travel_docs_image_three_id');

        if ($hotel->wasChanged('travel_docs_image_three_id') && !is_null($oldTravelDocsImageThree)) {
            Image::find($oldTravelDocsImageThree)->delete();
        }
    }

    public function forceDeleted(Hotel $hotel)
    {
        if ($hotel->travel_docs_cover_image()->exists()) {
            $hotel->travel_docs_cover_image->delete();
        }

        if ($hotel->travel_docs_image_two()->exists()) {
            $hotel->travel_docs_image_two->delete();
        }

        if ($hotel->travel_docs_image_three()->exists()) {
            $hotel->travel_docs_image_three->delete();
        }

        $oldImageIds = $hotel->images->pluck('id');
        $hotel->images()->sync(collect());
        Image::whereIn('id', $oldImageIds)->get()->each->delete();
    }
}
