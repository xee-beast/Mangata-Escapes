<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CalendarEventResource extends JsonResource
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
            'color' => $this->color,
            'is_default' => $this->is_default,
            'can' => [
                'view' => auth()->user()->can('manage event types'),
                'update' => auth()->user()->can('manage event types'),
                'delete' => auth()->user()->can('manage event types'),
            ]
        ];
    }
}