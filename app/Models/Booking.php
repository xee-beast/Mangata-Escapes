<?php

namespace App\Models;

use App\Models\Traits\InvoiceTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Illuminate\Notifications\Notifiable;

class Booking extends Model implements Sortable
{
    use InvoiceTrait, SoftDeletes, SortableTrait, Notifiable;

    protected $fillable = [
        'group_id',
        'special_requests',
        'notes',
        'order',
        'confirmed_at',
        'is_paid',
        'deposit',
        'deposit_type',
        'hotel_assistance',
        'hotel_preferences',
        'hotel_name',
        'room_category',
        'room_category_name',
        'check_in',
        'check_out',
        'budget',
        'transportation',
        'departure_gateway',
        'flight_preferences',
        'airline_membership_number',
        'known_traveler_number',
        'flight_message',
        'transportation_type',
        'transportation_submit_before',
        'transfer_id',
        'destination_id',
        'email',
        'reservation_leader_first_name',
        'reservation_leader_last_name',
        'travel_agent_id',
        'provider_id',
        'id_at_provider',
        'change_fee_date',
        'change_fee_amount',
        'staff_message',
        'balance_due_date',
        'cancellation_date',
        'terms_and_conditions',
        'travel_docs_cover_image_id',
        'travel_docs_image_two_id',
        'travel_docs_image_three_id',
        'booking_id',
        'is_bg_couple',
        'deleted_at',
    ];

    protected $dates = [
        'confirmed_at',
        'check_in',
        'check_out',
        'transportation_submit_before',
        'change_fee_date',
        'balance_due_date',
        'cancellation_date'
    ];

    public $sortable = [
        'order_column_name' => 'order'
    ];

    protected $casts = [
        'terms_and_conditions' => 'string',
        'is_bg_couple' => 'boolean'
    ];

    const TRANSPORTATION_TYPE_ROUND_TRIP = 1;
    const TRANSPORTATION_TYPE_ONE_WAY_AIRPORT_TO_HOTEL = 2;
    const TRANSPORTATION_TYPE_ONE_WAY_HOTEL_TO_AIRPORT = 3;

    protected static $one_way_transportation_types = [
        self::TRANSPORTATION_TYPE_ONE_WAY_AIRPORT_TO_HOTEL,
        self::TRANSPORTATION_TYPE_ONE_WAY_HOTEL_TO_AIRPORT
    ];

    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = strtolower($value);
    }

    public function setReservationLeaderFirstNameAttribute($value)
    {
        $this->attributes['reservation_leader_first_name'] = ucwords($value);
    }

    public function setReservationLeaderLastNameAttribute($value)
    {
        $this->attributes['reservation_leader_last_name'] = ucwords($value);
    }

    public function getFullNameAttribute()
    {
        return $this->attributes['reservation_leader_first_name'] . ' ' . $this->attributes['reservation_leader_last_name'];
    }

    public function routeNotificationForMail($notification)
    {
        return $this->email;
    }

    public function buildSortQuery()
    {
        return static::query()->withTrashed()->where('group_id', $this->group_id);
    }

    public function roomBlocks()
    {
        return $this->belongsToMany(RoomBlock::class, 'booking_room_block')
            ->using(BookingRoomBlock::class)
            ->withPivot('bed', 'check_in', 'check_out')
            ->withTimestamps();
    }

    public function group()
    {
        return $this->belongsTo('App\Models\Group')->withTrashed();
    }

    public function activeGroup()
    {
        return $this->group()->whereNull('deleted_at');
    }

    public function events()
    {
        return $this->hasMany('App\Models\Event');
    }

    public function booking_clients()
    {
        return $this->hasMany('App\Models\BookingClient');
    }

    public function clients()
    {
        return $this->hasMany('App\Models\BookingClient')->with('client');
    }

    public function guests()
    {
        return $this->hasManyThrough('App\Models\Guest', 'App\Models\BookingClient');
    }

    public function payments()
    {
        return $this->hasManyThrough('App\Models\Payment', 'App\Models\BookingClient');
    }

    public function trackedChanges()
    {
        return $this->morphMany('App\Models\TrackedChange', 'trackable');
    }

    public function guestChanges()
    {
        return $this->morphMany('App\Models\GuestChange', 'trackable');
    }

    public function paymentArrangements()
    {
        return $this->hasMany(BookingPaymentDate::class);
    }

    public function roomArrangements()
    {
        return $this->hasMany(RoomArrangement::class);
    }

    public function transportedGuests()
    {
        return $this->guests()->where('transportation', true);
    }

    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }

    public function destination()
    {
        return $this->belongsTo(Destination::class);
    }

    public function travel_agent()
    {
        return $this->belongsTo(TravelAgent::class);
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function travel_docs_cover_image()
    {
        return $this->belongsTo('App\Models\Image');
    }

    public function travel_docs_image_two()
    {
        return $this->belongsTo('App\Models\Image');
    }

    public function travel_docs_image_three()
    {
        return $this->belongsTo('App\Models\Image');
    }

    public function bookingDueDates() {
        return $this->hasMany(BookingDueDate::class);
    }

    public function getTotalAdultPax()
    {
        if ($this->trashed()) {
            return 0;
        }

        return collect($this->nights)
            ->pluck('nights')
            ->flatten()
            ->pluck('adults')
            ->flatten()
            ->filter(fn ($adult) => isset($adult->id))
            ->unique('id')
            ->count();
    }

    public function getDefaultTerms()
    {
        return view('pdf.static.individualBookingTermsConditions', ['cancellation_date' => $this->cancellation_date ? $this->cancellation_date->format('F d, Y') : null])->render();
    }
}
