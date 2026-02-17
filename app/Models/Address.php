<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $fillable = [
        'country_id', 'other_country',
        'state_id', 'other_state',
        'city',
        'line_1', 'line_2',
        'zip_code'
    ];

    protected $with = ['country', 'state'];

    /**
     * Get the address's country name.
     */
    public function getCountryNameAttribute() {
        return is_null($this->country) ? $this->other_country : $this->country->name;
    }

    /**
     * Get the address's state name.
     */
    public function getStateNameAttribute() {
        return is_null($this->state) ? $this->other_state : $this->state->name;
    }

    /**
     * Get the address's state abbreviation.
     */
    public function getStateAbbreviationAttribute() {
        return is_null($this->state) ? null : $this->state->abbreviation;
    }

    /**
     * Get the address's full address.
     */
    public function getFullAddressAttribute() {
        return $this->line_1 . (is_null($this->line_2) ? '' : ', ' . $this->line_2) . ', ' . $this->city . ', ' . (is_null($this->state) ? $this->other_state : $this->state->abbreviation) . ' ' . $this->zip_code . ', ' . $this->country_name;
    }

    /**
     * Get the address's country.
     */
    public function country()
    {
        return $this->belongsTo('App\Models\Country');
    }

    /**
     * Get the address's state.
     */
    public function state()
    {
        return $this->belongsTo('App\Models\State');
    }

    /**
     * Get the address's client.
     */
    public function client()
    {
        return $this->belongsTo('App\Models\Client');
    }

    /**
     * Get the cards linked with this address.
     */
    public function cards()
    {
        return $this->hasMany('App\Models\Card');
    }
}
