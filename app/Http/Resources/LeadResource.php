<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LeadResource extends JsonResource
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
            'isFit' => $this->is_fit,
            'isCanadian' => $this->is_canadian,
            'travelAgentId' => $this->travel_agent_id,
            'travelAgent' => new TravelAgentResource($this->whenLoaded('travelAgent')),
            'assignedAt' => $this->assigned_at ? $this->assigned_at->setTimezone('America/New_York')->format('m-d-Y H:i A') . ' EST' : null,
            'brideFirstName' => $this->bride_first_name,
            'brideLastName' => $this->bride_last_name,
            'brideName' => $this->bride_name,
            'groomFirstName' => $this->groom_first_name,
            'groomLastName' => $this->groom_last_name,
            'groomName' => $this->groom_name,
            'name' => $this->name,
            'departure' => $this->departure,
            'phone' => $this->phone,
            'textAgreement' => $this->text_agreement,
            'email' => $this->email,
            'venue' => $this->venue,
            'site' => $this->site,
            'numberOfPeople' => $this->number_of_people,
            'numberOfRooms' => $this->number_of_rooms,
            'destinations' => $this->destinations,
            'weddingDate' => $this->wedding_date ? $this->wedding_date->format('Y-m-d') : null,
            'weddingDateConfirmed' => $this->wedding_date_confirmed,
            'travelStartDate' => $this->travel_start_date ? $this->travel_start_date->format('Y-m-d') : null,
            'travelEndDate' => $this->travel_end_date ? $this->travel_end_date->format('Y-m-d') : null,
            'status' => $this->status,
            'travelAgentRequested' => $this->travel_agent_requested,
            'referralSource' => $this->referral_source,
            'facebookGroup' => $this->facebook_group,
            'referredBy' => $this->referred_by,
            'message' => $this->message,
            'contractSentOn' => $this->contract_sent_on ? $this->contract_sent_on->format('Y-m-d') : null,
            'lastAttempt' => $this->last_attempt ? $this->last_attempt->format('Y-m-d') : null,
            'respondedOn' => $this->responded_on ? $this->responded_on->format('Y-m-d') : null,
            'releaseRoomsBy' => $this->release_rooms_by ? $this->release_rooms_by->format('Y-m-d') : null,
            'balanceDueDate' => $this->balance_due_date ? $this->balance_due_date->format('Y-m-d') : null,
            'cancellationDate' => $this->cancellation_date ? $this->cancellation_date->format('Y-m-d') : null,
            'notes' => $this->notes,
            'contactedUsBy' => $this->contacted_us_by,
            'contactedUsDate' => $this->contacted_us_date ? $this->contacted_us_date->format('Y-m-d') : null,
            'leadProviders' => LeadProviderResource::collection($this->whenLoaded('leadProviders')),
            'can' => [
                'view' => auth()->user()->can('view', $this->resource),
                'update' => auth()->user()->can('update', $this->resource),
                'delete' => auth()->user()->can('delete', $this->resource),
            ],
        ];
    }
}
