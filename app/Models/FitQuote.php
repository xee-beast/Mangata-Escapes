<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FitQuote extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_client_id',
        'expiry_date_time',
        'accepted_at',
        'is_cancelled',
    ];

    public function bookingClient()
    {
        return $this->belongsTo(BookingClient::class);
    }
}
