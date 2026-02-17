<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlightManifest extends Model
{
    use HasFactory;

    protected $fillable = ['guest_id', 'phone_number', 'arrival_departure_airport_iata', 'arrival_departure_airport_timezone', 'arrival_departure_date', 'arrival_datetime', 'arrival_airport_id', 'arrival_airline', 'arrival_number', 'arrival_manual', 'arrival_date_mismatch_reason', 'departure_date', 'departure_datetime', 'departure_airport_id', 'departure_airline', 'departure_number', 'departure_manual', 'departure_date_mismatch_reason'];

    protected $dates = ['arrival_departure_date', 'arrival_datetime', 'departure_date', 'departure_datetime'];

    public function getAirlineName($iata_code) {
        $airline = Airline::where('iata_code', $iata_code)->first();
        
        return $airline ? $airline->name : $iata_code;
    }

    public function guest() {
        return $this->belongsTo(Guest::class);
    }

    public function arrivalAirport() {
        return $this->belongsTo(Airport::class, 'arrival_airport_id');
    }

    public function departureAirport() {
        return $this->belongsTo(Airport::class, 'departure_airport_id');
    }
}
