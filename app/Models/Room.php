<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{

    protected $fillable = [
        'name',
        'description',
        'size',
        'view',
        'image_id',
        'min_occupants', 'max_occupants',
        'adults_only',
        'max_adults', 'max_children',
        'min_adults_per_child', 'max_children_per_adult'
    ];

    protected $attributes = [
        'beds' => '["One King", "Two Doubles"]'
    ];

    protected $casts = [
        'adults_only' => 'boolean',
        'beds' => 'array'
    ];

    /**
     * Get the room's hotel.
     */
    public function hotel()
    {
        return $this->belongsTo('App\Models\Hotel');
    }

    /**
     * Get the room's image.
     */
    public function image()
    {
        return $this->belongsTo('App\Models\Image');
    }

    /**
     * Get the room blocks that the room is assigned to.
     */
    public function room_blocks()
    {
        return $this->hasMany('App\Models\RoomBlock');
    }

    public function getFormattedMaxOccupancyAttribute()
    {
        if ($this->adults_only) {
            return $this->max_occupants . ' (' . $this->max_occupants . ($this->max_occupants == 1 ? ' adult' : ' adults') . ')';
        }

        $combos = [];
        $allowedRatio = $this->max_children_per_adult / $this->min_adults_per_child;

        for ($adults = $this->max_adults; $adults >= 1; $adults--) {
            for ($children = $this->max_children; $children >= 0; $children--) {
                $total = $adults + $children;

                if ($total != $this->max_occupants) continue;

                if ($children > 0 && ($children / $adults) > $allowedRatio) continue;

                if ($children === 0) {
                    $combos[] = $adults . ' ' . ($adults === 1 ? 'adult' : 'adults');
                } else {
                    $combos[] = $adults . ' ' . ($adults === 1 ? 'adult' : 'adults') . ' / ' . $children . ' ' . ($children === 1 ? 'child' : 'children');
                }
            }
        }

        $combos = array_unique($combos);

        if (empty($combos)) {
            return $this->max_adults . ' (' . $this->max_adults . ' adults)';
        }

        return $this->max_occupants . ' (' . implode(' , ', $combos) . ')';
    }
}
