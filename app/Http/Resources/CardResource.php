<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CardResource extends JsonResource
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
            'client' => new ClientResource($this->whenLoaded('client')),
            'name' => $this->name,
            'type' => $this->type,
            'lastDigits' => substr($this->number, -4),
            'expMonth' => substr($this->expiration_date, 0, 2),
            'expYear' => substr($this->expiration_date, 2),
            $this->mergeWhen(auth()->user()->hasPermissionTo('process payments'), [
                'number' => $this->number,
                'code' => $this->code,
                'address' => new AddressResource($this->whenLoaded('address'))
            ]),
        ];
    }
}
