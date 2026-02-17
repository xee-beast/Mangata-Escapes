<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'country_id', 'name', 'abbreviation',
    ];

    /**
     * Get the state's country.
     */
    public function country()
    {
        return $this->belongsTo('App\Models\Country');
    }

    /**
     * Get the addresses that are in the state.
     */
    public function addresses()
    {
        return $this->hasMany('App\Models\Address');
    }
}
