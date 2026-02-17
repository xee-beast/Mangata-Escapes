<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DueDateResource extends JsonResource
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
            'date' => $this->date->format('Y-m-d'),
            'amount' => $this->amount,
            'type' => $this->type,
        ];
    }
}
