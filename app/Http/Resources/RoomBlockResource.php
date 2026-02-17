<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RoomBlockResource extends JsonResource
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
            'hotel' => new HotelBlockResource($this->whenLoaded('hotel_block')),
            'name'=> $this->room->name,
            'beds' => $this->room->beds,
            'size' => $this->room->size,
            'view' => $this->room->view,
            'startDate' => $this->start_date ? $this->start_date->format('Y-m-d') : null,
            'endDate' => $this->end_date ? $this->end_date->format('Y-m-d') : null,
            'splitDate' => $this->split_date ? $this->split_date->format('Y-m-d') : null,
            'minOccupants' => $this->room->min_occupants,
            'maxOccupants' => $this->room->max_occupants,
            'adultsOnly' => $this->room->adults_only,
            'maxAdults' => $this->room->max_adults,
            'maxChildren' => $this->room->max_children,
            'minAdultsPerChild' => $this->min_adults_per_child,
            'maxChildrenPerAdult' => $this->max_children_per_adult,
            'inventory' => $this->inventory,
            'soldOut' => $this->sold_out,
            'is_active'=> $this->is_active,
            'isVisible' => $this->is_visible,
            'booked' => $this->bookings()->count(),
            'hasBooking' => $this->bookings()->withTrashed()->exists(),
            'rates' => RateResource::collection($this->whenLoaded('rates')),
            'childRates' => ChildRateResource::collection($this->whenLoaded('child_rates')),
            'pivot' => $this->pivot,
            'can' => [
                'view' => auth()->user()->can('view', $this->resource),
                'update' => auth()->user()->can('update', $this->resource),
                'delete' => auth()->user()->can('delete', $this->resource),
            ]
        ];
    }
}
