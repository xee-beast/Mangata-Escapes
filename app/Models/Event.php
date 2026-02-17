<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $dates = [
        'start_date',
        'end_date'
    ];

    protected $fillable = [
        'booking_id', 'title', 'description', 'start_date', 'end_date', 'color', 'calendar_event_id'
    ];

    public function booking() {
        return $this->belongsTo(Booking::class);
    }

    public function calendarEvent()
    {
        return $this->belongsTo(CalendarEvent::class);
    }
}
