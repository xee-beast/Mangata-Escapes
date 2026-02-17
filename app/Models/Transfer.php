<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'primary_phone_number',
        'secondary_phone_number_label',
        'secondary_phone_number_value',
        'whatsapp_number',
        'missed_or_changed_flight',
        'arrival_procedure',
        'departure_procedure',
        'display_image_id',
        'app_image_id',
        'app_link'
    ];

    public function display_image()
    {
        return $this->belongsTo('App\Models\Image');
    }

    public function app_image()
    {
        return $this->belongsTo('App\Models\Image');
    }


    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function groupAirports()
    {
        return $this->hasMany(GroupAirport::class);
    }

    public function airports()
    {
        return $this->hasMany(Airport::class);
    }
}
