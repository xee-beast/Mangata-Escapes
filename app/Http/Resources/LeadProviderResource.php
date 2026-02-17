<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LeadProviderResource extends JsonResource
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
            'leadId' => $this->lead_id,
            'lead' => new LeadResource($this->whenLoaded('lead')),
            'providerId' => $this->provider_id,
            'provider' => new ProviderResource($this->whenLoaded('provider')),
            'idAtProvider' => $this->id_at_provider,
            'specialistId' => $this->specialist_id,
            'specialist' => new SpecialistResource($this->whenLoaded('specialist')),
            'leadHotels' => LeadHotelResource::collection($this->whenLoaded('leadHotels')),
        ];
    }
}
