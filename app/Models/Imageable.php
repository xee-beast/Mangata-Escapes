<?php

namespace App\Models;

use App\Models\Image;
use Illuminate\Database\Eloquent\Relations\MorphPivot;

class Imageable extends MorphPivot
{
    public static function boot()
    {
        parent::boot();

        static::deleted(function ($imageable)  {
            Image::find($imageable->image_id)->delete();
        });
    }
}
