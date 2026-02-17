<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AirlineResource extends JsonResource
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
            'name' => $this->name,
            'iata_code' => $this->iata_code,
            'can' => [
                'view' => auth()->user()->can('manage airlines'),
                'update' => auth()->user()->can('manage airlines'),
                'delete' => auth()->user()->can('manage airlines'),
            ]
        ];
    }
}
