<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = ['card_id', 'amount', 'confirmed_at', 'notes', 'cancelled_at', 'card_declined'];

    protected $dates = ['confirmed_at', 'cancelled_at'];

    protected $casts = [
        'card_declined' => 'boolean'
    ];

    /**
     * Get the booking the payment is for.
     */
    public function booking_client()
    {
        return $this->belongsTo('App\Models\BookingClient');
    }

    /**
     * Get the card for this payment.
     */
    public function card()
    {
        return $this->belongsTo('App\Models\Card')->with('address');
    }
}
