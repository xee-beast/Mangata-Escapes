<?php

namespace App\Observers;

use App\Models\Image;
use Illuminate\Support\Facades\Storage;

class ImageObserver
{
    protected function disk()
    {
        return config('filesystems.media_disk') ?? config('filesystems.default');
    }

    /**
     * Handle the image "created" event.
     *
     * @param  \App\Models\Image  $image
     * @return void
     */
    public function created(Image $image)
    {
        $disk = $this->disk();
        Storage::disk($disk)->copy('tmp/' . $image->path, $image->path);
        Storage::disk($disk)->setVisibility($image->path, 'public');
    }

    /**
     * Handle the image "deleted" event.
     *
     * @param  \App\Models\Image  $image
     * @return void
     */
    public function deleted(Image $image)
    {
        $disk = $this->disk();
        Storage::disk($disk)->delete($image->path);
    }
}
