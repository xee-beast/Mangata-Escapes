<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class InsuranceRate extends Model
{
    protected $fillable = ['name', 'start_date', 'description', 'type', 'rates', 'url'];

    protected $dates = ['start_date'];

    protected $casts = ['rates' => 'array'];

    /**
     * Set the start date.
     *
     * @param  string  $value
     * @return void
     */
    public function setStartDateAttribute($value)
    {
        $this->attributes['start_date'] = is_null($value) ? null : Carbon::parse($value);
    }

    /**
     * Get the insurance rate's provider.
     */
    public function provider()
    {
        return $this->belongsTo('App\Models\Provider');
    }

    /**
     * Get the insurance rate's groups.
     */
    public function groups()
    {
        return $this->hasMany('App\Models\Group');
    }   
}
