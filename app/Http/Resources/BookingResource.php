<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
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
            'order' => $this->order,
            'group' => new GroupResource($this->whenLoaded('group')),
            'rooms' => RoomBlockResource::collection($this->whenLoaded('roomBlocks')),
            'roomArrangements' => RoomArrangementResource::collection($this->whenLoaded('roomArrangements')),
            'clients' => BookingClientResource::collection($this->whenLoaded('clients')),
            'specialRequests' => $this->special_requests,
            'quoteAccepted' => (!$this->group || ($this->group && $this->group->is_fit)) ? ($this->booking_clients()->whereDoesntHave('acceptedFitQuote')->doesntExist() ? true : false) : false,
            'notes' => $this->notes,
            'total' => $this->total,
            'totalPayments' => $this->payment_total,
            'minimumDeposit' => $this->minimum_deposit,
            'pendingPayments' => $this->payments()->whereNull('confirmed_at')->whereNull('cancelled_at')->count(),
            'pendingChanges' => new TrackedChangesResource($this->whenLoaded('trackedChanges', $this->trackedChanges->firstWhere('confirmed_at', null))),
            'guestChanges' => GuestChangeResource::collection($this->whenLoaded('guestChanges', $this->guestChanges->where('confirmed_at', null)->whereNull('deleted_at'))),
            'deposit' => $this->deposit,
            'depositType' => $this->deposit_type,
            'isPaid' => $this->is_paid,
            'hotelAssistance' => $this->hotel_assistance,
            'hotelPreferences' => $this->hotel_preferences,
            'hotelName' => $this->hotel_name,
            'roomCategory' => $this->room_category,
            'roomCategoryName' => $this->room_category_name,
            'checkIn' => is_null($this->check_in) ? null : $this->check_in->format('Y-m-d'),
            'checkOut' => is_null($this->check_out) ? null : $this->check_out->format('Y-m-d'),
            'budget' => $this->budget,
            'transportation' => $this->transportation,
            'departureGateway' => $this->departure_gateway,
            'flightPreferences' => $this->flight_preferences,
            'airlineMembershipNumber' => $this->airline_membership_number,
            'knownTravelerNumber' => $this->known_traveler_number,
            'flightMessage' => $this->flight_message,
            'transportationType' => $this->transportation_type,
            'transportationSubmitBefore' => is_null($this->transportation_submit_before) ? null : $this->transportation_submit_before->format('Y-m-d'),
            'transfer' => new TransferResource($this->whenLoaded('transfer')),
            'destination' => new DestinationResource($this->whenLoaded('destination')),
            'email' => $this->email,
            'reservationLeaderFirstName' => $this->reservation_leader_first_name,
            'reservationLeaderLastName' => $this->reservation_leader_last_name,
            'agent' => new TravelAgentResource($this->whenLoaded('travel_agent')),
            'provider' => new ProviderResource($this->whenLoaded('provider')),
            'paymentArrangements' => BookingPaymentDateResource::collection($this->whenLoaded('paymentArrangements')),
            'providerId' => $this->id_at_provider,
            'changeFeeDate' => is_null($this->change_fee_date) ? null : $this->change_fee_date->format('Y-m-d'),
            'changeFeeAmount' => $this->change_fee_amount,
            'staffMessage' => $this->staff_message,
            'balanceDueDate' => is_null($this->balance_due_date) ? null : $this->balance_due_date->format('Y-m-d'),
            'cancellationDate' => is_null($this->cancellation_date) ? null : $this->cancellation_date->format('Y-m-d'),
            'bookingDueDates' => BookingDueDateResource::collection($this->whenLoaded('bookingDueDates')),
            'termsAndConditions' => $this->group ? null : (empty($this->terms_and_conditions) ? $this->getDefaultTerms() : $this->terms_and_conditions),
            'confirmedAt' => $this->confirmed_at,
            'deletedAt' => $this->deleted_at,
            'createdAt' => $this->created_at,
            'invoiceUrl' => $this->group ? route('dashboard.invoice', ['group' => $this->group_id, 'booking' => $this->id]) : route('dashboard.individual-bookings-invoice', ['booking' => $this->id]),
            'travelDocumentsUrl' => $this->group ? route('dashboard.travel-documents', ['group' => $this->group_id, 'booking' => $this->id]) : route('dashboard.individual-bookings-travel-documents', ['booking' => $this->id]),
            'isPaymentArrangementActive' => count($this->paymentArrangements) > 0 ? true : false,
            'travelDocsCoverImage' => new ImageResource($this->whenLoaded('travel_docs_cover_image')),
            'travelDocsImageTwo' => new ImageResource($this->whenLoaded('travel_docs_image_two')),
            'travelDocsImageThree' => new ImageResource($this->whenLoaded('travel_docs_image_three')),
            'bookingId' => $this->booking_id,
            'isBgCouple' => $this->is_bg_couple,
            'can' => [
                'view' => auth()->user()->can('view', $this->resource),
                'update' => auth()->user()->can('update', $this->resource),
                'delete' => auth()->user()->can('delete', $this->resource),
                'restore' => auth()->user()->can('restore', $this->resource),
                'forceDelete' => auth()->user()->can('forceDelete', $this->resource),
                'confirm' => auth()->user()->can('confirm', $this->resource),
                'confirmChanges' => auth()->user()->can('confirmChanges', $this->resource),
                'viewClients' => auth()->user()->can('viewAny', \App\Models\BookingClient::class),
                'viewPayments' => auth()->user()->can('viewAny', \App\Models\Payment::class),
            ]
        ];
    }
}
