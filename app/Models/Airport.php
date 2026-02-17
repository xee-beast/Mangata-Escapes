<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Airport extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'airport_code', 'timezone', 'transfer_id',
    ];

    public function destination(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\Destination');
    }

    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany('App\Models\GroupAirport');
    }

    public function arrivalFlightManifests(): HasMany
    {
        return $this->hasMany(FlightManifest::class, 'arrival_airport_id');
    }

    public function departureFlightManifests(): HasMany
    {
        return $this->hasMany(FlightManifest::class, 'departure_airport_id');
    }

    public function hotelAirportRates(): HasMany
    {
        return $this->hasMany(HotelAirportRate::class);
    } 
}
