<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Scout\Searchable;

class Client extends Model
{
    use Notifiable, Searchable;

    public function routeNotificationForMail($notification)
    {
        return [$this->email => $this->name];
    }

    protected $fillable = ['first_name', 'last_name', 'email'];

    /**
     * Set the client's email.
     *
     * @param  string  $value
     * @return void
     */
    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = strtolower(trim($value));
    }

    /**
     * Set the client's first name.
     *
     * @param  string  $value
     * @return void
     */
    public function setFirstNameAttribute($value)
    {
        $this->attributes['first_name'] = ucwords($value);
    }

    /**
     * Set the client's last name.
     *
     * @param  string  $value
     * @return void
     */
    public function setLastNameAttribute($value)
    {
        $this->attributes['last_name'] = ucwords($value);
    }

    /**
     * Get the client's full name.
     *
     * @param  string  $value
     * @return void
     */
    public function getNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Get the client's addresses
     */
    public function addresses()
    {
        return $this->hasMany('App\Models\Address');
    }   

    /**
     * Get the client's bookings.
     */
    public function bookings()
    {
        return $this->hasMany('App\Models\BookingClient')->with('booking');
    }

    public function bookingsWithTrashed()
    {
        return $this->hasMany('App\Models\BookingClient')->with(['booking' => function ($query) {
            $query->withTrashed();
        }]);
    }
    
    /**
     * Get the client's cards.
     */
    public function cards()
    {
        return $this->hasMany('App\Models\Card');
    }

    public function toSearchableArray() {
        return [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
        ];
    }  
}
