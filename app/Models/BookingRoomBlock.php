<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class BookingRoomBlock extends Pivot
{
    use HasFactory;

    protected $table = 'booking_room_block';

    protected $fillable = [
        'booking_id',
        'room_block_id',
        'bed',
        'check_in',
        'check_out',
    ];

    protected $dates = ['check_in', 'check_out'];

    public function setCheckInAttribute($value)
    {
        $this->attributes['check_in'] = Carbon::parse($value)->format('Y-m-d');
    }

    public function setCheckOutAttribute($value)
    {
        $this->attributes['check_out'] = Carbon::parse($value)->format('Y-m-d');
    }
}
