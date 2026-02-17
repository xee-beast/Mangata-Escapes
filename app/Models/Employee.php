<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    /**
     * Set the employee's first name.
     *
     * @param  string  $value
     * @return void
     */
    public function setFirstNameAttribute($value)
    {
        $this->attributes['first_name'] = ucwords($value);
    }

    /**
     * Set the employee's last name.
     *
     * @param  string  $value
     * @return void
     */
    public function setLastNameAttribute($value)
    {
        $this->attributes['last_name'] = ucwords($value);
    }

    /**
     * Get the employee's travel agent model.
     */
    public function travel_agent()
    {
        return $this->hasOne('App\Models\TravelAgent');
    }

    /**
     * Get the employee's user model.
     */
    public function user()
    {
        return $this->belongsTo('App\User');
    }
}
