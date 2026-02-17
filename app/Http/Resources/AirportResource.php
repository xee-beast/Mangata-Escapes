<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AirportResource extends JsonResource
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
            'airport_code' => $this->airport_code,
            'timezone' => $this->timezone,
            'transfer_id' => $this->transfer_id,
            'transfer' => new TransferResource($this->whenLoaded('transfer')),
            'can' => [
                'view' => auth()->user()->can('manage airports'),
                'update' => auth()->user()->can('manage airports'),
                'delete' => auth()->user()->can('manage airports'),
            ]
        ];
    }
}
