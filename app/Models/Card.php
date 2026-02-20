<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Card extends Model
{
    protected $fillable = ['name', 'number', 'type', 'expiration_date', 'code', 'address_id'];

    /**
     * Set the card's name.
     *
     * @param  string  $value
     * @return void
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = ucwords($value);
    }

    /**
     * Encrypt the card's number.
     *
     * @param  string  $value
     * @return void
     */
    public function setNumberAttribute($value)
    {
        $this->attributes['number'] = Crypt::encryptString($value);
    }

    /**
     * Decrypt the card's number.
     *
     * @param  string  $value
     * @return void
     */
    public function getNumberAttribute()
    {
        return Crypt::decryptString($this->attributes['number']);
    }

    /**
     * Retrieve the last 4 digits of the card's number.
     *
     * @return string
     */
    public function getLastDigitsAttribute()
    {
        return substr($this->number, -4);
    }

    /**
     * Retrieve the card's epiration month.
     *
     * @return string
     */
    public function getExpMonthAttribute()
    {
        return substr($this->expiration_date, 0, 2);
    }

    /**
     * Retrieve card's expiration year.
     *
     * @return string
     */
    public function getExpYearAttribute()
    {
        return substr($this->expiration_date, 2);
    }

    /**
     * Encrypt the card's cvv code.
     *
     * @param  string  $value
     * @return void
     */
    public function setCodeAttribute($value)
    {
        $this->attributes['code'] = Crypt::encryptString($value);
    }

    /**
     * Decrypt the card's cvv code.
     *
     * @param  string  $value
     * @return void
     */
    public function getCodeAttribute()
    {
        return Crypt::decryptString($this->attributes['code']);
    }

    /**
     * Get the card's client.
     */
    public function client()
    {
        return $this->belongsTo('App\Models\Client');
    }

    /**
     * Get the card's address.
     */
    public function address()
    {
        return $this->belongsTo('App\Models\Address');
    }

    /**
     * Get the payments this card has processed.
     */
    public function payments()
    {
        return $this->hasMany('App\Models\Payment');
    }
}
