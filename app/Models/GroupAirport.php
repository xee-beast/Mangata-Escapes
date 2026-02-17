<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class GroupAirport extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'group_id', 
        'airport_id', 
        'transfer_id',
        'transportation_rate', 
        'single_transportation_rate', 
        'one_way_transportation_rate',
        'default'
    ];

    public $timestamps = false;

    public function group()
    {
        return $this->belongsTo('App\Models\Group');
    }

    public function airport()
    {
        return $this->belongsTo('App\Models\Airport');
    }

    public function transfer()
    {
        return $this->belongsTo('App\Models\Transfer');
    }
}
