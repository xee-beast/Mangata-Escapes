<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LeadHotelResource extends JsonResource
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
            'leadProviderId' => $this->lead_provider_id,
            'leadProvider' => new LeadProviderResource($this->whenLoaded('leadProvider')),
            'brandId' => $this->brand_id,
            'brand' => new BrandResource($this->whenLoaded('brand')),
            'hotel' => $this->hotel,
            'requestedOn' => $this->requested_on ? $this->requested_on->format('Y-m-d') : null,
            'weddingDate' => $this->wedding_date ? $this->wedding_date->format('Y-m-d') : null,
            'travelStartDate' => $this->travel_start_date ? $this->travel_start_date->format('Y-m-d') : null,
            'travelEndDate' => $this->travel_end_date ? $this->travel_end_date->format('Y-m-d') : null,
            'receivedOn' => $this->received_on ? $this->received_on->format('Y-m-d') : null,
            'proposalDocument' => new FileResource($this->whenLoaded('proposal_document')),
        ];
    }
}
