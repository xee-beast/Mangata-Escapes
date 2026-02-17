<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
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
            'size' => $this->size,
            'view' => $this->view,
            'image' => new ImageResource($this->whenLoaded('image')),
            'minOccupants' => $this->min_occupants,
            'maxOccupants' => $this->max_occupants,
            'adultsOnly' => $this->adults_only,
            'maxAdults' => $this->max_adults,
            'maxChildren' => $this->max_children,
            'minAdultsPerChild' => $this->min_adults_per_child,
            'maxChildrenPerAdult' => $this->max_children_per_adult,
            'beds' => $this->beds,
            'can' => [
                'view' => auth()->user()->can('view', $this->resource),
                'update' => auth()->user()->can('update', $this->resource),
                'delete' => auth()->user()->can('delete', $this->resource),
            ]
        ];
    }
}
