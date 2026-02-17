<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InsuranceRateResource extends JsonResource
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
            'provider' => new ProviderResource($this->whenLoaded('provider')),
            'name' => $this->name,
            'description' => $this->description,
            'startDate' => is_null($this->start_date) ? null : $this->start_date->format('Y-m-d'),
            'calculateBy' => $this->type,
            'rates' => $this->rates,
            'url' => $this->url,
            'groups' => GroupResource::collection($this->whenLoaded('groups')),
            'can' => [
                'view' => auth()->user()->can('view', $this->resource),
                'update' => auth()->user()->can('update', $this->resource),
                'delete' => auth()->useR()->can('delete', $this->resource),
            ]
        ];
    }
}
