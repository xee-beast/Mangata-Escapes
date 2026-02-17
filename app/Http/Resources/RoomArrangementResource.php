<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RoomArrangementResource extends JsonResource
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
            'booking' => new BookingResource($this->whenLoaded('booking')),
            'hotel'=> $this->hotel,
            'room'=> $this->room,
            'bed' => $this->bed,
            'checkIn' => $this->check_in->format('Y-m-d'),
            'checkOut' => $this->check_out->format('Y-m-d'),
        ];
    }
}
