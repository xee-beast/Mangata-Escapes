<?php

namespace App\Observers;

use App\Models\File;
use Illuminate\Support\Facades\Storage;

class FileObserver
{
    protected function disk()
    {
        return config('filesystems.media_disk') ?? config('filesystems.default');
    }

    public function created(File $file)
    {
        $disk = $this->disk();
        Storage::disk($disk)->copy('tmp/' . $file->path, $file->path);
        Storage::disk($disk)->setVisibility($file->path, 'public');
    }

    public function deleted(File $file)
    {
        $disk = $this->disk();
        Storage::disk($disk)->delete($file->path);
    }
}
