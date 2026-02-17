<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingPaymentDate extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'group_id',
        'booking_client_id',
        'due_date',
        'amount',
    ];

    protected $dates = [
        'due_date',
    ];

    public function setDueDateAttribute($value)
    {
        $this->attributes['due_date'] = Carbon::parse($value)->format('Y-m-d');
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function bookingClient() {
        return $this->belongsTo(BookingClient::class);
    }
}
