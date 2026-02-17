<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class DueDate extends Model
{
    public $timestamps = false;

    protected $dates = ['date'];

    protected $fillable = ['group_id', 'date', 'amount', 'type'];

    /**
     * Set the due date.
     *
     * @param  string  $value
     * @return void
     */
    public function setDateAttribute($value)
    {
        $this->attributes['date'] = Carbon::parse($value);
    }

    /**
     * Get the type and amount text.
     *
     * @return string
     */
    public function getKeyAttribute()
    {
        $key = '';
        
        if ($this->type == 'nights') {
            $key .= intval($this->amount) . ' nights';
        } else if ($this->type == 'percentage') {
            $key .= intval($this->amount) . '%';
        } else if ($this->type == 'price') {
            $key .= '$' . $this->amount;
        }

        return $key . ' due date';
    }

    /**
     * Get the group the due date is assigned to.
     */
    public function group()
    {
        return $this->belongsTo('App\Models\Group');
    }
}
