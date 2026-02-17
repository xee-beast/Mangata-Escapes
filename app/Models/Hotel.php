<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Hotel extends Model
{
    use SoftDeletes;

    /**
     * Get the destination that the hotel is at.
     */
    public function destination()
    {
        return $this->belongsTo('App\Models\Destination');
    }

    /**
     * Get the hotel's travel docs cover image.
     */
    public function travel_docs_cover_image()
    {
        return $this->belongsTo('App\Models\Image');
    }

    /**
     * Get the hotel's travel docs second image.
     */
    public function travel_docs_image_two()
    {
        return $this->belongsTo('App\Models\Image');
    }

    /**
     * Get the hotel's travel docs third image.
     */
    public function travel_docs_image_three()
    {
        return $this->belongsTo('App\Models\Image');
    }

    /**
     * Get the hotel's images.
     */
    public function images()
    {
        return $this->morphToMany('App\Models\Image', 'imageable')->using('App\Models\Imageable');
    }

    /**
     * Get the rooms that are in the hotel.
     */
    public function rooms()
    {
        return $this->hasMany('App\Models\Room');
    }

    /**
     * Get the hotel blocks that the hotel is assigned to.
     */
    public function hotel_blocks()
    {
        return $this->hasMany('App\Models\HotelBlock');
    }

    /**
     * Get the hotel's airport rates.
     */
    public function hotelAirportRates()
    {
        return $this->hasMany(HotelAirportRate::class);
    }
}
