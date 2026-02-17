<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TravelAgent extends Model
{
    protected $fillable = ['user_id', 'first_name', 'last_name', 'email', 'is_active'];
    
    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function setFirstNameAttribute($value)
    {
        $this->attributes['first_name'] = ucwords($value);
    }

    public function setLastNameAttribute($value)
    {
        $this->attributes['last_name'] = ucwords($value);
    }

    public function getNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function groups()
    {
        return $this->hasMany('App\Models\Group');
    }
    
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function leads()
    {
        return $this->hasMany(Lead::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
