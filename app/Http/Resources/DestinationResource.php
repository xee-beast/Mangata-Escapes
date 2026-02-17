<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DestinationResource extends JsonResource
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
            'country' => new CountryResource($this->country),
            'name' => $this->name,
            'airports' => AirportResource::collection($this->whenLoaded('airports')),
            'weatherDescription' => $this->weather_description,
            'outletAdapter' => $this->outlet_adapter,
            'taxDescription' => $this->tax_description,
            'languageDescription' => $this->language_description,
            'currencyDescription' => $this->currency_description,
            'hotels' => HotelResource::collection($this->whenLoaded('hotels')),
            'image' => new ImageResource($this->whenLoaded('image')),
            'can' => [
                    'view' => auth()->user()->can('view', $this->resource),
                    'update' => auth()->user()->can('update', $this->resource),
                    'delete' => auth()->user()->can('delete', $this->resource),
                ],
        ];
    }
}
