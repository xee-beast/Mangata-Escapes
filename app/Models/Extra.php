<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Extra extends Model
{
    protected $fillable = ['description', 'price', 'quantity', 'booking_client_id', 'created_at', 'updated_at'];

    /**
     * Get the client the extra belongs to.
     */
    public function bookingClient() {
        return $this->belongsTo('App\Models\BookingClient');
    }
}
