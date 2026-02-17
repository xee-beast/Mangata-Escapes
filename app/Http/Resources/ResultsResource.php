<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ResultsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $group = $this->group;

        if ($group) {
            $date = $group->event_date->format('Y-m-d');
            $name = $group->bride_last_name . ' & ' . $group->groom_last_name;
            $providerAbbreviation = $group->provider->abbreviation;
            $providerId = $group->id_at_provider;
            $url = 'groups/' . $group->id . '/bookings/' . $this->id;
            $type = $group->is_fit ? 'Group FIT' : '';
        } else {
            $date = $this->check_in->format('Y-m-d');
            $name = $this->reservation_leader_first_name . ' ' . $this->reservation_leader_last_name;
            $providerAbbreviation = $this->provider ? $this->provider->abbreviation : '-';
            $providerId = $this->id_at_provider ?? '-';
            $url = 'individual-bookings/' . $this->id;
            $type = 'Individual FIT';
        }

        return [
            'date' => $date,
            'name' => $name,
            'providerAbbreviation' => $providerAbbreviation,
            'providerId' => $providerId,
            'roomNumber' => $this->order,
            'guests' => GuestResource::collection($this->whenLoaded('guests')),
            'url' => $url,
            'type' => $type,
        ];
    }
}