<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'is_fit',
        'is_canadian',
        'travel_agent_id',
        'assigned_at',
        'bride_first_name',
        'bride_last_name',
        'groom_first_name',
        'groom_last_name',
        'departure',
        'phone',
        'text_agreement',
        'email',
        'venue',
        'site',
        'number_of_people',
        'number_of_rooms',
        'destinations',
        'wedding_date',
        'wedding_date_confirmed',
        'travel_start_date',
        'travel_end_date',
        'status',
        'travel_agent_requested',
        'referral_source',
        'facebook_group',
        'referred_by',
        'message',
        'contract_sent_on',
        'last_attempt',
        'responded_on',
        'release_rooms_by',
        'balance_due_date',
        'cancellation_date',
        'notes',
        'contacted_us_by',
        'contacted_us_date'
    ];

    protected $casts = [
        'is_fit' => 'boolean',
        'is_canadian' => 'boolean',
        'assigned_at' => 'datetime',
        'text_agreement' => 'boolean',
        'wedding_date' => 'date',
        'wedding_date_confirmed' => 'boolean',
        'travel_start_date' => 'date',
        'travel_end_date' => 'date',
        'contract_sent_on' => 'date',
        'last_attempt' => 'date',
        'responded_on' => 'date',
        'release_rooms_by' => 'date',
        'balance_due_date' => 'date',
        'cancellation_date' => 'date',  
        'contacted_us_date' =>  'date',
    ];

    public function getBrideNameAttribute()
    {
        $first = trim($this->bride_first_name);
        $last = trim($this->bride_last_name);

        return trim($first . ' ' . $last);
    }

    public function getGroomNameAttribute()
    {
        $first = trim($this->groom_first_name);
        $last = trim($this->groom_last_name);

        return trim($first . ' ' . $last);
    }

    public function getNameAttribute()
    {
        $bride = $this->bride_name;
        $groom = $this->groom_name;

        if ($bride && $groom) {
            return "{$bride} & {$groom}";
        }

        return $bride ?: $groom;
    }

    public function travelAgent()
    {
        return $this->belongsTo(TravelAgent::class);
    }

    public function leadProviders()
    {
        return $this->hasMany(LeadProvider::class);
    }

    public function group()
    {
        return $this->hasOne(Group::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', '!=', 'Declined')
            ->where(function ($query) {
                $query->where('status', '!=', 'Signed K')
                    ->orWhereDoesntHave('group');
            });
    }
}
