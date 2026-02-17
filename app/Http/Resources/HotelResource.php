<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class HotelResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'url' => $this->url,
            'destination' => new DestinationResource($this->whenLoaded('destination')),
            'travelDocsCoverImage' => new ImageResource($this->whenLoaded('travel_docs_cover_image')),
            'travelDocsImageTwo' => new ImageResource($this->whenLoaded('travel_docs_image_two')),
            'travelDocsImageThree' => new ImageResource($this->whenLoaded('travel_docs_image_three')),
            'images' => ImageResource::collection($this->whenLoaded('images')),
            'rooms' => RoomResource::collection($this->whenLoaded('rooms')),
            'hotelAirportRates' => HotelAirportRateResource::collection($this->whenLoaded('hotelAirportRates')),
            'deletedAt' => $this->deleted_at,
            'can' => [
                'view' => auth()->user()->can('view', $this->resource),
                'update' => auth()->user()->can('update', $this->resource),
                'delete' => auth()->user()->can('delete', $this->resource),
                'viewRooms' => auth()->user()->can('viewAny', \App\Models\Room::class),
            ],
        ];
    }
}
