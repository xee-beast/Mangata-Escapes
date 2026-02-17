<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'division',
    ];

    /**
     * Set the country's name.
     *
     * @param  string  $value
     * @return void
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = ucwords($value);
    }

    /**
     * Get the states that belong to the country.
     */
    public function states()
    {
        return $this->hasMany('App\Models\State')->orderBy('name');
    }

    /**
     * Get the addresses that are in the country.
     */
    public function addresses()
    {
        return $this->hasMany('App\Models\Address');
    }

    /**
     * Get the destinations that are part of the country.
     */
    public function destinations()
    {
        return $this->hasMany('App\Models\Destination');
    }
}
