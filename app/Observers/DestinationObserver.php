<?php

namespace App\Observers;

use App\Models\Destination;
use App\Models\Image;

class DestinationObserver
{
    public function updated(Destination $destination)
    {
        $oldImage = $destination->getOriginal('image_id');

        if ($destination->wasChanged('image_id') && !is_null($oldImage)) {
            Image::find($oldImage)->delete();
        }
    }

    public function deleted(Destination $destination)
    {
        if ($destination->image()->exists()) {
            $destination->image->delete();
        }
    }
}
