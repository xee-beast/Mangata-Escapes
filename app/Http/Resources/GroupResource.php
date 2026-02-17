<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use App\Models\Image;
use Illuminate\Support\Str;

class GroupResource extends JsonResource
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
            'fit' => $this->is_fit,
            'brideFirstName' => $this->bride_first_name,
            'brideLastName' => $this->bride_last_name,
            'groomFirstName' => $this->groom_first_name,
            'groomLastName' => $this->groom_last_name,
            'email' => $this->email,
            'secondaryEmail' => $this->secondary_email,
            'password' => $this->password,
            'slug' => $this->slug,
            'isActive' => $this->is_active,
            'couplesSitePassword' => $this->couples_site_password,
            'eventDate' => $this->event_date->format('Y-m-d'),
            'image' => new ImageResource($this->whenLoaded('image')),
            'attritionImage' => new ImageResource($this->whenLoaded('attrition_image')),
            'groupAttritionDueDates' => GroupAttritionDueDateResource::collection($this->whenLoaded('groupAttritionDueDates')),
            'message' => $this->message,
            'destination' => new DestinationResource($this->whenLoaded('destination')),
            'weddingLocation' => $this->wedding_location,
            'venueName' => $this->venue_name,
            'groupFaqs' => $this->whenLoaded('groupFaqs', function () { return $this->groupFaqs; }),
            'agent' => new TravelAgentResource($this->whenLoaded('travel_agent')),
            'provider' => new ProviderResource($this->provider),
            'providerId' => $this->id_at_provider,
            'insuranceRate' => new InsuranceRateResource($this->whenLoaded('insurance_rate')),
            'useFallbackInsurance' => $this->use_fallback_insurance,
            'transportation' => $this->transportation,
            'acceptsNewBookings' => $this->accepts_new_bookings,
            'transportationType' => $this->transportation_type,
            'transportationSubmitBefore' => is_null($this->transportation_submit_before) ? null : $this->transportation_submit_before->format('Y-m-d'),
            'minNights' => $this->min_nights,
            'deposit' => $this->deposit,
            'depositType' => $this->deposit_type,
            'changeFeeAmount' => $this->change_fee_amount,
            'changeFeeDate' => $this->change_fee_date,
            'dueDate' => $this->balance_due_date->format('Y-m-d'),
            'cancellationDate' => $this->cancellation_date->format('Y-m-d'),
            'dueDates' => DueDateResource::collection($this->whenLoaded('due_dates')),
            'hotels' => HotelBlockResource::collection($this->whenLoaded('hotels')),
            'hasBookings' => count($this->bookings()->withTrashed()->get()) > 0,
            'pendingBookings' => $this->bookings()->where(function ($query) { $query->whereNull('confirmed_at')->orWhereHas('payments', function ($query) { $query->whereNull('confirmed_at')->whereNull('cancelled_at'); })->orWhereHas('trackedChanges', function ($query) { $query->whereNull('confirmed_at'); })->orWhereHas('guestChanges', function ($query) { $query->whereNull('confirmed_at')->whereNull('deleted_at'); }); })->count(),
            'bookings' => BookingResource::collection($this->whenLoaded('bookings')),
            'notes' => $this->notes,
            'bannerMessage' => $this->banner_message,
            'staffMessage' => $this->staff_message,
            'pastBrideMessage' => $this->past_bride_message,
            'showAsPastBride' => $this->show_as_past_bride,
            'bookingsExportUrl' => route('dashboard.bookings-export', ['group' => $this->id]),
            'flightManifestsExportUrl' => route('dashboard.flight-manifests-export', ['group' => $this->id]),
            'airports' => GroupAirportResource::collection($this->whenLoaded('airports')),
            'defaultAirport' => new GroupAirportResource($this->whenLoaded('defaultAirport')),
            'deletedAt' => $this->deleted_at,
            'disableInvoiceSplitting' => $this->disable_invoice_splitting,
            'disableNotifications' => $this->disable_notifications,
            'termsAndConditions' => empty($this->terms_and_conditions) ? $this->getDefaultTerms() : $this->terms_and_conditions,
            'can' => [
                'view' => auth()->user()->can('view', $this->resource),
                'update' => auth()->user()->can('update', $this->resource),
                'delete' => auth()->user()->can('delete', $this->resource),
                'viewAccomodations' => auth()->user()->can('viewAny', \App\Models\RoomBlock::class),
                'viewBookings' => auth()->user()->can('viewAny', \App\Models\Booking::class),
            ]
        ];
    }
}
