<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FitRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_client_id',
        'accommodation',
        'insurance',
    ];

    public function bookingClient()
    {
        return $this->belongsTo(BookingClient::class);
    }
}
