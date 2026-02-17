<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RoomBlock extends Model
{
    protected $fillable = ['hotel_block_id', 'room_id', 'min_adults_per_child', 'max_children_per_adult', 'inventory', 'start_date', 'end_date', 'split_date', 'is_active', 'is_visible'];

    protected $with = ['room'];

    protected $dates = ['start_date', 'end_date', 'split_date'];

    protected $casts = [
        'sold_out' => 'boolean',
        'is_visible' => 'boolean'
    ];

    /**
     * Set the room block start date.
     *
     * @param  string  $value
     * @return void
     */
    public function setStartDateAttribute($value)
    {
        $this->attributes['start_date'] = $value ? Carbon::parse($value) : null;
    }

    /**
     * Set the room block end date.
     *
     * @param  string  $value
     * @return void
     */
    public function setEndDateAttribute($value)
    {
        $this->attributes['end_date'] = $value ? Carbon::parse($value) : null;
    }

    /**
     * Set the room block split date.
     *
     * @param  string  $value
     * @return void
     */
    public function setSplitDateAttribute($value)
    {
        $this->attributes['split_date'] = $value ? Carbon::parse($value) : null;
    }

    /**
     * Get the room block's hotel block.
     */
    public function hotel_block()
    {
        return $this->belongsTo('App\Models\HotelBlock');
    }

    /**
     * Get the room block's room.
     */
    public function room()
    {
        return $this->belongsTo('App\Models\Room');
    }

    /**
     * Get the room block's bookings.
     */
    public function bookings()
    {
        return $this->belongsToMany(Booking::class, 'booking_room_block')
            ->using(BookingRoomBlock::class)
            ->withPivot('bed', 'check_in', 'check_out')
            ->withTimestamps();
    }

    /**
     * Get the rate block's rates.
     */
    public function rates()
    {
        return $this->hasMany('App\Models\Rate')->orderBy('occupancy');
    }

    /**
     * Sync the rate block's rates.
     *
     * @param array $rates
     * @param int  $minOccupants
     * @param int $maxOccupants
     * @param bool $splitDates
     */
    public function syncRates($rates)
    {
        $rateSync = [];
        for ($occupancy = 1; $occupancy <= ($this->room->max_adults ?? $this->room->max_occupants); $occupancy++) {
            $rate = [];

            if ($occupancy < $this->room->min_occupants) {
                $rate['rate'] = ($this->room->min_occupants / $occupancy) * $rates[0]['rate'];
                $rate['provider_rate'] = ($this->room->min_occupants / $occupancy) * ($rates[0]['providerRate'] ?? $rates[0]['rate']);
                $rate['split_rate'] = is_null($this->split_date) ? null : (($this->room->min_occupants / $occupancy) * $rates[0]['splitRate']);
                $rate['split_provider_rate'] =  is_null($this->split_date) ? null : (($this->room->min_occupants / $occupancy) * ($rates[0]['splitProviderRate'] ?? $rates[0]['splitRate']));
            } else if ($occupancy >= ($this->room->min_occupants + count($rates))) {
                $rate['rate'] = $rates[count($rates) - 1]['rate'];
                $rate['provider_rate'] = $rates[count($rates) - 1]['providerRate'] ?? $rates[count($rates) - 1]['rate'];
                $rate['split_rate'] = is_null($this->split_date) ? null : ($rates[count($rates) - 1]['splitRate']);
                $rate['split_provider_rate'] =  is_null($this->split_date) ? null : ($rates[count($rates) - 1]['splitProviderRate'] ?? $rates[count($rates) - 1]['splitRate']);
            } else {
                $rate['rate'] = $rates[$occupancy - $this->room->min_occupants]['rate'];
                $rate['provider_rate'] = $rates[$occupancy - $this->room->min_occupants]['providerRate'] ?? $rates[$occupancy - $this->room->min_occupants]['rate'];
                $rate['split_rate'] = is_null($this->split_date) ? null : ($rates[$occupancy - $this->room->min_occupants]['splitRate']);
                $rate['split_provider_rate'] =  is_null($this->split_date) ? null : ($rates[$occupancy - $this->room->min_occupants]['splitProviderRate'] ?? $rates[$occupancy - $this->room->min_occupants]['splitRate']);
            }

            $savedRate = $this->rates()->updateOrCreate(
                ['occupancy' => $occupancy],
                [
                    'rate' => $rate['rate'],
                    'provider_rate' => $rate['provider_rate'] ?? $rate['rate'],
                    'split_rate' => is_null($this->split_date) ? null : $rate['split_rate'],
                    'split_provider_rate' => is_null($this->split_date) ? null : ($rate['split_provider_rate'] ?? $rate['split_rate']),
                ]
            );
            array_push($rateSync, $savedRate->id);
        }
        $this->rates()->whereNotIn('id', $rateSync)->delete();
    }

    /**
     * Get the rate block's child rates.
     *
     * @param array $childRates
     * @param bool $splitDates
     */
    public function child_rates()
    {
        return $this->hasMany('App\Models\ChildRate')->orderBy('from');
    }

    /**
     * Sync the rate block's child rates.
     */
    public function syncChildRates($childRates)
    {
        $childRateSync = [];

        foreach ($childRates as $index => $childRate) {
            $savedChildRate = $this->child_rates()->updateOrCreate(
                [
                    'from' => $childRate['from'], 
                    'to' => $childRate['to'], 
                    'uuid' => $childRate['uuid'] ?? null
                ],
                [
                    'uuid' => $childRate['uuid'] ?? (string) Str::uuid(),
                    'rate' => $childRate['rate'],
                    'provider_rate' => $childRate['providerRate'] ?? $childRate['rate'],
                    'split_rate' => is_null($this->split_date) ? null : $childRate['splitRate'],
                    'split_provider_rate' => is_null($this->split_date) ? null : ($childRate['providerSplitRate'] ?? $childRate['splitRate']),
                ]
            );
            array_push($childRateSync, $savedChildRate->id);
        }
        $this->child_rates()->whereNotIn('id', $childRateSync)->delete();
    }

    public function getLiteralStartDateAttribute()
    {
        return ;
    }    
}
