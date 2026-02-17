<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Destination extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'airport_code',
        'tax_description',
        'language_description',
        'currency_description',
    ];

    public function setAirportCodeAttribute($value)
    {
        $this->attributes['airport_code'] = strtoupper($value);
    }

    public function country()
    {
        return $this->belongsTo('App\Models\Country');
    }

    public function image()
    {
        return $this->belongsTo(Image::class);
    }

    public function hotels()
    {
        return $this->hasMany('App\Models\Hotel');
    }

    public function groups()
    {
        return $this->hasMany('App\Models\Group');
    }

    public function airports(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\Airport');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}
