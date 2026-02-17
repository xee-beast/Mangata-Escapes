<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class TransportationType extends Model
{
    protected $fillable = ['description'];

    public $timestamps = false;
    
    /**
     * Get the guests with the specific transportation type
     */
    public function guests()
    {
        return $this->belongsTo('App\Models\Guest');
    }
}
