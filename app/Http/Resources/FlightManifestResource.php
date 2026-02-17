<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FlightManifestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'guest' => $this->relationLoaded('guest') ? new GuestResource($this->guest) : ['id' => $this->guest_id],
            'phoneNumber' => $this->phone_number,
            'arrivalDepartureAirportIata' => $this->arrival_departure_airport_iata,
            'arrivalDepartureAirportTimezone' => $this->arrival_departure_airport_timezone,
            'arrivalDepartureDate' => $this->arrival_departure_date ? $this->arrival_departure_date->format('Y-m-d') : null,
            'arrivalDateTime' =>  isset($this->arrival_datetime) ? $this->arrival_datetime->setTimezone(isset($this->arrival_airport_id) ? $this->arrivalAirport->timezone : 'UTC')->toDateTimeString() : null,
            'arrivalAirportId' => $this->arrival_airport_id,
            'arrivalAirline' => $this->arrival_airline,
            'arrivalNumber' => $this->arrival_number,
            'arrivalManual' => $this->arrival_manual,
            'arrivalDateMismatchReason' => $this->arrival_date_mismatch_reason,
            'departureDate' => $this->departure_date ? $this->departure_date->format('Y-m-d') : null,
            'departureDateTime' =>  isset($this->departure_datetime) ? $this->departure_datetime->setTimezone(isset($this->departure_airport_id) ? $this->departureAirport->timezone : 'UTC')->toDateTimeString() : null,
            'departureAirportId' => $this->departure_airport_id,
            'departureAirline' => $this->departure_airline,
            'departureNumber' => $this->departure_number,
            'departureManual' => $this->departure_manual,
            'departureDateMismatchReason' => $this->departure_date_mismatch_reason,
        ];
    }
}
