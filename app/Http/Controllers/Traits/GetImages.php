<?php

namespace App\Http\Controllers\Traits;

use App\Models\Image;

trait GetImages
{
    public function getImages($images)
    {
        $uploadedImages = collect();

        foreach($images as $image) {
            $uploadedImage = $this->getImage($image);
            $uploadedImages->push($uploadedImage);
        }

        return $uploadedImages;
    }

    public function getImage($image)
    {
        $uploadedImage = Image::firstOrCreate(
            ['id' => $image['uuid']],
            ['path' => str_replace('tmp/', '', $image['path'])]
        );

        return $uploadedImage;
    }
}
