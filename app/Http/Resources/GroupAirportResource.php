<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Airport;

class GroupAirportResource extends JsonResource
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
            'airport' => $this->airport_id,
            'transfer' => $this->transfer_id,
            'transferProvider' => new TransferResource($this->whenLoaded('transfer')),
            'originAirport' => Airport::where('id', $this->airport_id)->first(),
            'transportationRate' => $this->transportation_rate,
            'singleTransportationRate' => $this->single_transportation_rate,
            'oneWayTransportationRate' => $this->one_way_transportation_rate,
            'default' => $this->default,
        ];
    }
}
