<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Group extends Model
{
    use SoftDeletes, Notifiable;

    protected $dates = [
        'event_date',
        'transportation_submit_before',
        'cancellation_date',
        'balance_due_date',
        'change_fee_date'
    ];

    protected $fillable = [
        'lead_id',
        'accepts_new_bookings',
        'show_as_past_bride',
        'image_id',
        'attrition_image_id',
        'disable_notifications',
    ];

    protected $casts = [
        'use_fallback_insurance' => 'boolean',
        'transportation' => 'boolean',
        'disable_invoice_splitting' => 'boolean',
        'disable_notifications' => 'boolean',
        'terms_and_conditions' => 'string',
        'accepts_new_bookings' => 'boolean'
    ];

    /**
     * Set the bride's first name.
     *
     * @param  string  $value
     * @return void
     */
    public function setBrideFirstNameAttribute($value)
    {
        $this->attributes['bride_first_name'] = ucwords($value);
    }

    /**
     * Set the bride's last name.
     *
     * @param  string  $value
     * @return void
     */
    public function setBrideLastNameAttribute($value)
    {
        $this->attributes['bride_last_name'] = ucwords($value);
    }

    /**
     * Set the groom's first name.
     *
     * @param  string  $value
     * @return void
     */
    public function setGroomFirstNameAttribute($value)
    {
        $this->attributes['groom_first_name'] = ucwords($value);
    }

    /**
     * Set the groom's last name.
     *
     * @param  string  $value
     * @return void
     */
    public function setGroomLastNameAttribute($value)
    {
        $this->attributes['groom_last_name'] = ucwords($value);
    }

    /**
     * Set the group owner's email address.
     *
     * @param  string  $value
     * @return void
     */
    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = strtolower($value);
    }

    /**
     * Set the group's secondary email address.
     *
     * @param  string  $value
     * @return void
     */
    public function setSecondaryEmailAttribute($value)
    {
        $this->attributes['secondary_email'] = !empty($value) ? strtolower($value) : null;
    }

    /**
     * Set the slug.
     *
     * @param  string  $value
     * @return void
     */
    public function setSlugAttribute($value)
    {
        $this->attributes['slug'] = strtolower($value);
    }

    /**
     * Set the event date.
     *
     * @param  string  $value
     * @return void
     */
    public function setEventDateAttribute($value)
    {
        $this->attributes['event_date'] = Carbon::parse($value);
    }

    public function setChangeFeeDateAttribute($value)
    {
        $this->attributes['change_fee_date'] = Carbon::parse($value);
    }

    /**
     * Set the cancellation due date.
     *
     * @param  string  $value
     * @return void
     */
    public function setCancellationDateAttribute($value)
    {
        $this->attributes['cancellation_date'] = Carbon::parse($value);
    }

    /**
     * Set the balance due date.
     *
     * @param  string  $value
     * @return void
     */
    public function setBalanceDueDateAttribute($value)
    {
        $this->attributes['balance_due_date'] = Carbon::parse($value);
    }

    /**
     * Get the group's full name.
     *
     * @return string
     */
    public function getFullNameAttribute()
    {
        return $this->attributes['bride_first_name'] . ' ' . $this->attributes['bride_last_name'] . ' & ' . $this->attributes['groom_first_name'] . ' ' . $this->attributes['groom_last_name'];
    }

    /**
     * Get the group's formal name.
     *
     * @return string
     */
    public function getFormalNameAttribute()
    {
        return $this->attributes['bride_last_name'] . ' & ' . $this->attributes['groom_last_name'];
    }

    /**
     * Get the group name.
     *
     * @return string
     */
    public function getNameAttribute()
    {
        return $this->attributes['bride_first_name'] . ' & ' . $this->attributes['groom_first_name'];
    }

    /**
     * Get the group's destination.
     */
    public function destination()
    {
        return $this->belongsTo('App\Models\Destination');
    }

    /**
     * Get the group's provider.
     */
    public function provider()
    {
        return $this->belongsTo('App\Models\Provider');
    }

    public function groupFaqs()
    {
        return $this->hasMany(GroupFaq::class);
    }

    /**
     * Get the group's insurance rate.
     */
    public function getInsuranceRate($date = null)
    {
        if ($this->use_fallback_insurance) {
            return $this->insurance_rate;
        }

        $insuranceRate = $this->provider->insurance_rates()->where('start_date', '<=', $date ?? now())->orderBy('start_date', 'desc')->first();

        return $insuranceRate ?? $this->insurance_rate;
    }

    /**
     * Get the group's fallback insurance rate.
     */
    public function insurance_rate()
    {
        return $this->belongsTo('App\Models\InsuranceRate');
    }

    /**
     * Get the couple's image.
     */
    public function image()
    {
        return $this->belongsTo('App\Models\Image', 'image_id');
    } 

    /**
     * Get the group's due dates.
     */
    public function due_dates()
    {
        return $this->hasMany('App\Models\DueDate')->orderBy('date');
    }

    /**
     * Get the group's attrition due dates.
     */
    public function groupAttritionDueDates()
    {
        return $this->hasMany('App\Models\GroupAttritionDueDate');
    }

    /**
     * Get the group's hotel blocks.
     */
    public function hotels()
    {
        return $this->hasMany('App\Models\HotelBlock')->with('hotel');
    }

    /**
     * Get the travel agent assigned to this group.
     */
    public function travel_agent()
    {
        return $this->belongsTo('App\Models\TravelAgent');
    }

    /**
     * Get the group's bookings.
     */
    public function bookings()
    {
        return $this->hasMany('App\Models\Booking');
    }

    public function bookingsWithTrashed()
    {
        return $this->hasMany(Booking::class)->withTrashed();
    }

    /**
     * Get the attrition's image.
     */
    public function attrition_image()
    {
        return $this->belongsTo('App\Models\Image', 'attrition_image_id');
    }    

    /**
     * Get the group's clients.
     */
    public function clients()
    {
        return $this->hasManyThrough('App\Models\BookingClient', 'App\Models\Booking')->with('client');
    }

    public function airports()
    {
        return $this->hasMany('App\Models\GroupAirport');
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function paymentArrangements()
    {
        return $this->hasMany(BookingPaymentDate::class);
    }

    public function defaultAirport() {
        return $this->airports()->where('default', true)->first();
    }

    public function getTransportationRateAttribute()
    {
        if($this->transportation && !empty($this->defaultAirport())) {
            return $this->defaultAirport()->transportation_rate;
        }

        return $this->attributes['transportation_rate'];
    }

    public function getSingleTransportationRateAttribute()
    {
        if($this->transportation && !empty($this->defaultAirport())) {
            return $this->defaultAirport()->single_transportation_rate;
        }        
        return $this->attributes['single_transportation_rate'];
    }

    public function getOneWayTransportationRateAttribute() {
        if($this->transportation && !empty($this->defaultAirport())) {
            return $this->defaultAirport()->one_way_transportation_rate;
        }   
        return $this->attributes['one_way_transportation_rate'];
    }

    /**
     * Route notifications for the mail channel.
     *
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return array|string
     */
    public function routeNotificationForMail($notification)
    {
        if (!empty($this->secondary_email)) {
            return [$this->email, $this->secondary_email];
        }

        return $this->email;
    }

    public function getDefaultTerms()
    {
        if ($this->is_fit) {
            return view('pdf.static.fitTermsConditions', ['group' => $this])->render();
        } else {
            return view('pdf.static.termsConditions', ['group' => $this])->render();
        }
    }
}
