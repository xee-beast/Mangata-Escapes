<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class HotelBlockResource extends JsonResource
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
            'name' => $this->hotel->name,
            'url' => $this->hotel->url,
            'rooms' => RoomBlockResource::collection($this->whenLoaded('rooms')),
        ];
    }
}
