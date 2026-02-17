<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProviderResource extends JsonResource
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
            'abbreviation' => $this->abbreviation,
            'phoneNumber' => $this->phone_number,
            'email' => $this->email,
            'groups' => GroupResource::collection($this->whenLoaded('groups')),
            'specialists' => SpecialistResource::collection($this->whenLoaded('specialists')),
            'can' => [
                'view' => auth()->user()->can('view', $this->resource),
                'update' => auth()->user()->can('update', $this->resource),
                'delete' => auth()->user()->can('delete', $this->resource),
                'viewInsuranceRates' => auth()->user()->can('viewAny', \App\Models\InsuranceRate::class),
            ]
        ];
    }
}
