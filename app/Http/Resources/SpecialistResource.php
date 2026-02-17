<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SpecialistResource extends JsonResource
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
            'providerId' => $this->provider_id,
            'provider' => new ProviderResource($this->whenLoaded('provider')),
            'name' => $this->name,
            'email' => $this->email,
            'leadProvidersCount' => $this->lead_providers_count ?? 0,
        ];
    }
}
