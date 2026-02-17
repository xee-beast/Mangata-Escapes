<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rate extends Model
{
    public $timestamps = false;

    protected $fillable = ['room_block_id', 'occupancy', 'rate', 'provider_rate', 'split_rate', 'split_provider_rate'];

    /**
     * Get the rate's room block.
     */
    public function room_block()
    {
        return $this->belongsTo('App\Models\RoomBlock');
    }
}
