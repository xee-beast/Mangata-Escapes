<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class HotelAirportRateResource extends JsonResource
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
            'hotel_id' => $this->hotel_id,
            'airport_id' => $this->airport_id,
            'transportation_rate' => $this->transportation_rate,
            'single_transportation_rate' => $this->single_transportation_rate,
            'one_way_transportation_rate' => $this->one_way_transportation_rate,
            'airport' => new AirportResource($this->whenLoaded('airport')),
            'can' => [
                'update' => auth()->user()->can('update', $this->hotel),
                'delete' => auth()->user()->can('update', $this->hotel),
            ]
        ];
    }
}
