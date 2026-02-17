<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChildRate extends Model
{
    public $timestamps = false;

    protected $fillable = ['uuid', 'room_block_id', 'from', 'to', 'rate', 'provider_rate', 'split_rate', 'split_provider_rate'];

    /**
     * Get the child rate's room block.
     */
    public function room_block()
    {
        return $this->belongsTo('App\Models\RoomBlock');
    }
}
