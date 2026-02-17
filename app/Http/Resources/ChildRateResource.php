<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ChildRateResource extends JsonResource
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
            'uuid' => $this->uuid,
            'from' => $this->from,
            'to' => $this->to,
            'rate' => $this->rate,
            'providerRate' => $this->provider_rate,
            'splitRate' => $this->split_rate,
            'splitProviderRate' => $this->split_provider_rate,
        ];
    }
}
