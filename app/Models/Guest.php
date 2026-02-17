<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Guest extends Model
{
    use SoftDeletes, Searchable;
    
    protected $fillable = ['booking_client_id', 'first_name', 'last_name', 'gender', 'birth_date', 'check_in', 'check_out', 'insurance', 'transportation', 'transportation_type', 'custom_group_airport', 'departure_pickup_time', 'created_at', 'updated_at', 'deleted_at'];

    protected $casts = [
        'insurance' => 'boolean'
    ];

    protected $touches = ['booking_client'];

    protected $dates = ['birth_date', 'check_in', 'check_out'];

    /**
     * Set the guest's first name.
     *
     * @param  string  $value
     * @return void
     */
    public function setFirstNameAttribute($value)
    {
        $this->attributes['first_name'] = ucwords($value);
    }

    /**
     * Set the guest's last name.
     *
     * @param  string  $value
     * @return void
     */
    public function setLastNameAttribute($value)
    {
        $this->attributes['last_name'] = ucwords($value);
    }

    /**
     * Set the birth date.
     *
     * @param  string  $value
     * @return void
     */
    public function setBirthDateAttribute($value)
    {
        $this->attributes['birth_date'] = Carbon::parse($value)->format('Y-m-d');
    }

    /**
     * Set the check in date.
     *
     * @param  string  $value
     * @return void
     */
    public function setCheckInAttribute($value)
    {
        $this->attributes['check_in'] = Carbon::parse($value)->format('Y-m-d');
    }

    /**
     * Set the check out date.
     *
     * @param  string  $value
     * @return void
     */
    public function setCheckOutAttribute($value)
    {
        $this->attributes['check_out'] = Carbon::parse($value)->format('Y-m-d');
    }

    /**
     * Get the guest's full name.
     *
     * @param  string  $value
     * @return void
     */
    public function getNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Get the guest's booking.
     */
    public function booking_client()
    {
        return $this->belongsTo('App\Models\BookingClient');
    }

    public function bookingClientWithTrashed()
    {
        return $this->booking_client()->whereHas('booking', function ($query) {
            $query->withTrashed();
        });
    }

    /**
     * Get the guest's flight manifest.
     */
    public function flight_manifest()
    {
        return $this->hasOne('App\Models\FlightManifest');
    } 
    
    public function toSearchableArray() {
        return [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
        ];
    }
    
    public function customGroupAirport()
    {
        if(!$this->custom_group_airport) return null;

        if ($this->booking_client->bookingWithTrashed->group) {
            $query = GroupAirport::where('id', $this->custom_group_airport);
        } else {
            $query = Airport::where('id', $this->custom_group_airport);
        }

        return $query->exists() ? $query->first() : null;
    }   
}