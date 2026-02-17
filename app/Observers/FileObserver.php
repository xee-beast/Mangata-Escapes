<?php

namespace App\Observers;

use App\Models\File;
use Illuminate\Support\Facades\Storage;

class FileObserver
{
    public function created(File $file)
    {
        Storage::copy('tmp/' . $file->path, $file->path);
        Storage::setVisibility($file->path, 'public');
    }

    public function deleted(File $file)
    {
        Storage::delete($file->path);
    }
}
