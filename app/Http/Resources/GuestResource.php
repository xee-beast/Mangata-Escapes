<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GuestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'firstName' => $this->first_name,
            'lastName' => $this->last_name,
            'gender' => $this->gender,
            'birthDate' => $this->birth_date->format('Y-m-d'),
            'checkIn' => $this->check_in->format('Y-m-d'),
            'checkOut' => $this->check_out->format('Y-m-d'),
            'insurance' => $this->insurance,
            'transportation' => $this->transportation,
            'transportation_type' => $this->transportation_type,
            'clientId' => $this->when(!$this->relationLoaded('booking_client'), $this->booking_client_id),
            'client' => new BookingClientResource($this->whenLoaded('booking_client')),
            'flightManifest' => new FlightManifestResource($this->whenLoaded('flight_manifest')),
            'deleted_at' => $this->deleted_at ? $this->deleted_at->format('Y-m-d') : null,
            'customGroupAirport' => $this->custom_group_airport,
            'departurePickupTime' => $this->departure_pickup_time,
        ];
    }
}
