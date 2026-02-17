<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomArrangement extends Model
{
    use HasFactory;

    protected $fillable = ['booking_id', 'hotel', 'room', 'bed', 'check_in', 'check_out'];

    protected $dates = ['check_in', 'check_out'];

    public function booking() {
        return $this->belongsTo(Booking::class);
    }
}
