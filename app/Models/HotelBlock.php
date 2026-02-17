<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HotelBlock extends Model
{
    public $timestamps = false;

    protected $fillable = ['group_id', 'hotel_id'];

    protected $with = ['hotel'];

    /**
     * Get the hotel block's group.
     */
    public function group()
    {
        return $this->belongsTo('App\Models\Group');
    }

    /**
     * Get the hotel block's hotel.
     */
    public function hotel()
    {
        return $this->belongsTo('App\Models\Hotel')->withTrashed();
    }

    /**
     * Get the hotel's blocked rooms.
     */
    public function rooms()
    {
        return $this->hasMany('App\Models\RoomBlock')->with('room');
    }

    /**
     * Get the sorted rooms by availability and price.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getSortedRoomsAttribute()
    {
        return $this->rooms->filter(function ($room) {
            return $room->is_visible;
        })->sortBy(function ($room) {
            return $room->sold_out ? PHP_INT_MAX : 
                  ($room->rates->max('rate') ?? PHP_INT_MAX);
        });
    }
}
