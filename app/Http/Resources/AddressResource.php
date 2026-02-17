<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
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
            'country' => is_null($this->country) ? $this->other_country : $this->country->name,
            'state' => is_null($this->state) ? $this->other_state : $this->state->name,
            'stateAbbreviation' => is_null($this->state) ? null : $this->state->abbreviation,
            'city' => $this->city,
            'line1' => $this->line_1,
            'line2' => $this->line_2,
            'zipCode' => $this->zip_code
        ];
    }
}
