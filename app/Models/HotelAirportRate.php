<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HotelAirportRate extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'hotel_id',
        'airport_id',
        'transportation_rate',
        'single_transportation_rate',
        'one_way_transportation_rate',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'transportation_rate' => 'decimal:2',
        'single_transportation_rate' => 'decimal:2',
        'one_way_transportation_rate' => 'decimal:2',
    ];

    /**
     * Get the hotel that owns the rate.
     */
    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    /**
     * Get the airport that owns the rate.
     */
    public function airport()
    {
        return $this->belongsTo(Airport::class);
    }
}
