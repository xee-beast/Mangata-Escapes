<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TransferResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'primaryPhoneNumber' => $this->primary_phone_number,
            'secondaryPhoneNumberLabel' => $this->secondary_phone_number_label,
            'secondaryPhoneNumberValue' => $this->secondary_phone_number_value,
            'whatsappNumber' => $this->whatsapp_number,
            'missedOrChangedFlight' => $this->missed_or_changed_flight,
            'arrivalProcedure' => $this->arrival_procedure,
            'departureProcedure' => $this->departure_procedure,
            'displayImage' => new ImageResource($this->whenLoaded('display_image')),
            'appImage' => new ImageResource($this->whenLoaded('app_image')),
            'appLink' => $this->app_link,
            'groupsCount' => $this->groupAirports()->whereHas('group', function($query) { $query->withTrashed();})->distinct('group_id')->count('group_id'),
            'can' => [
                'view' => auth()->user()->can('manage transfers'),
                'update' => auth()->user()->can('manage transfers'),
                'delete' => auth()->user()->can('manage transfers'),
            ]
        ];
    }
}
