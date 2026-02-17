<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    const CV = 1;
    const TI = 2;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'abbreviation',
        'phone_number',
        'email'
    ];

    public function insurance_rates()
    {
        return $this->hasMany('App\Models\InsuranceRate');
    }

    public function groups()
    {
        return $this->hasMany('App\Models\Group');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function leadProviders()
    {
        return $this->hasMany(LeadProvider::class);
    }

    public function specialists()
    {
        return $this->hasMany(Specialist::class);
    }
}
