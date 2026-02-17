<?php

namespace App\Observers;

use App\Models\Image;
use App\Models\Room;

class RoomObserver
{
    /**
     * Handle the room "updated" event.
     *
     * @param  \App\Models\Room  $room
     * @return void
     */
    public function updated(Room $room)
    {
        $oldImage = $room->getOriginal('image_id');

        if($room->wasChanged('image_id') && !is_null($oldImage)) {
            Image::find($oldImage)->delete();
        }
    }

    /**
     * Handle the room "deleted" event.
     *
     * @param  \App\Models\Room  $room
     * @return void
     */
    public function deleted(Room $room)
    {
        if($room->image()->exists()) {
            $room->image->delete();
        }
    }
}
