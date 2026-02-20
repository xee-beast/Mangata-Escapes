<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ImageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $disk = config('filesystems.media_disk') ?? config('filesystems.default');
        return [
            'uuid' => $this->id,
            'path' => $this->path,
            'storagePath' => Storage::disk($disk)->url($this->path),
        ];
    }
}
