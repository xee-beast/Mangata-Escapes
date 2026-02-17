<?php

namespace App\Observers;

use App\Models\Image;
use App\Models\Transfer;

class TransferObserver
{
    /**
     * Handle the transfer "updated" event.
     *
     * @param  \App\Models\Transfer  $transfer
     * @return void
     */
    public function updated(Transfer $transfer)
    {
        $oldDisplayImage = $transfer->getOriginal('display_image_id');
        $oldAppImage = $transfer->getOriginal('app_image_id');

        if ($transfer->wasChanged('display_image_id') && !is_null($oldDisplayImage)) {
            Image::find($oldDisplayImage)->delete();
        }

        if ($transfer->wasChanged('app_image_id') && !is_null($oldAppImage)) {
            Image::find($oldAppImage)->delete();
        }
    }

    /**
     * Handle the transfer "deleted" event.
     *
     * @param  \App\Models\Transfer  $transfer
     * @return void
     */
    public function deleted(Transfer $transfer)
    {
        if ($transfer->display_image()->exists()) {
            $transfer->display_image->delete();
        }

        if ($transfer->app_image()->exists()) {
            $transfer->app_image->delete();
        }
    }
}
