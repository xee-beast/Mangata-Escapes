<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class BookingClient extends Model 
{
    use Searchable;

    protected $fillable = ['client_id', 'first_name', 'last_name', 'card_id', 'telephone', 'insurance', 'insurance_signed_at'];

    protected $casts = [
        'insurance' => 'boolean'
    ];
    protected $dates = ['insurance_signed_at'];

    const TRANSPORTATION_TYPE_ROUND_TRIP = 1;
    const TRANSPORTATION_TYPE_ONE_WAY_AIRPORT_TO_HOTEL = 2;
    const TRANSPORTATION_TYPE_ONE_WAY_HOTEL_TO_AIRPORT = 3;

    protected static $one_way_transportation_types = [
        self::TRANSPORTATION_TYPE_ONE_WAY_AIRPORT_TO_HOTEL,
        self::TRANSPORTATION_TYPE_ONE_WAY_HOTEL_TO_AIRPORT
    ];

    /**
     * Set the booking's client first name.
     *
     * @param  string  $value
     * @return void
     */
    public function setFirstNameAttribute($value)
    {
        $this->attributes['first_name'] = ucwords($value);
    }

    /**
     * Set the booking's client last name.
     *
     * @param  string  $value
     * @return void
     */
    public function setLastNameAttribute($value)
    {
        $this->attributes['last_name'] = ucwords($value);
    }

    /**
     * Get the booking client's full name.
     *
     * @return string
     */
    public function getNameAttribute()
    {
        return $this->attributes['first_name'] . ' ' . $this->attributes['last_name'];
    }

    /**
     * Get the booking.
     */
    public function booking() {
        return $this->belongsTo('App\Models\Booking');
    }

    public function bookingWithTrashed() {
        return $this->belongsTo(Booking::class, 'booking_id')->withTrashed();
    }

    /**
     * Get the client.
     */
    public function client() {
        return $this->belongsTo('App\Models\Client');
    }

    /**
     * Get the card on file.
     */
    public function card() {
        return $this->belongsTo('App\Models\Card');
    }

    /**
     * Get the guests.
     */
    public function guests() {
        return $this->hasMany('App\Models\Guest');
    }

    /**
     * Get the client's extras.
     */
    public function extras() {
        return $this->hasMany('App\Models\Extra');
    }

    public function paymentArrangements()
    {
        return $this->hasMany(BookingPaymentDate::class);
    }

    /**
     * Get the payments.
     */
    public function payments() {
        return $this->hasMany('App\Models\Payment');
    }

    public function toSearchableArray() {
        return [
            'booking_id' => $this->booking_id,
            'reservation_code' => $this->reservation_code,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'telephone' => $this->telephone,
        ];
    }
    
    /**
     * Get the booking's transported guests.
     */
    public function transportedGuests()
    {
        return $this->guests()->where('transportation', true);
    }

    public function fitRate()
    {
        return $this->hasOne(FitRate::class);
    }

    public function fitQuotes()
    {
        return $this->hasMany(FitQuote::class);
    }

    public function pendingFitQuote()
    {
        return $this->hasOne(FitQuote::class)->whereNull('accepted_at')->where('is_cancelled', false)->where('expiry_date_time', '>', now());
    }

    public function acceptedFitQuote()
    {
        return $this->hasOne(FitQuote::class)->whereNotNull('accepted_at');
    }

    public function discardedFitQuote()
    {
        return $this->hasOne(FitQuote::class)
            ->where(function ($query) {
                $query->where('is_cancelled', true)
                    ->orWhere(function ($q) {
                        $q->whereNull('accepted_at')->where('is_cancelled', false)->where('expiry_date_time', '<=', now());
                    });
            })
            ->latest('created_at');
    }
}
