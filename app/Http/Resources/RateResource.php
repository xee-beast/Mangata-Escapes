<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RateResource extends JsonResource
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
            'occupancy' => $this->occupancy,
            'rate' => $this->rate,
            'providerRate' => $this->provider_rate,
            'splitRate' => $this->split_rate,
            'splitProviderRate' => $this->split_provider_rate,
        ];
    }
}
