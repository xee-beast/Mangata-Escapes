<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Guest;

class BookingClientResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $guests = Guest::withTrashed()->with('flight_manifest')->where(['booking_client_id' => $this->id])->get();

        return [
            'id' => $this->id,
            'reservationCode' => $this->reservation_code,
            'booking' => new BookingResource($this->whenLoaded('booking')),
            'client' => new ClientResource($this->whenLoaded('client')),
            'firstName' => $this->first_name,
            'lastName' => $this->last_name,
            'card' => new CardResource($this->whenLoaded('card')),
            'phone' => $this->telephone,
            'insurance' => $this->insurance,
            'insuranceSignedAt' => $this->insurance_signed_at,
            'pendingFitQuote' => new FitQuoteResource($this->pendingFitQuote),
            'acceptedFitQuote' => new FitQuoteResource($this->acceptedFitQuote),
            'discardedFitQuote' => new FitQuoteResource($this->whenLoaded('discardedFitQuote')),
            'guests' => GuestResource::collection($guests),
            'extras' => ExtraResource::collection($this->whenLoaded('extras')),
            'fitRate' => new FitRateResource($this->whenLoaded('fitRate')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'pendingPayments' => $this->payments()->whereNull('confirmed_at')->whereNull('cancelled_at')->count(),
            'can' => [
                'view' => auth()->user()->can('view', $this->resource),
                'update' => auth()->user()->can('update', $this->resource),
                'delete' => auth()->user()->can('delete', $this->resource),
                'viewPayments' => auth()->user()->can('viewAny', \App\Models\Payment::class),
            ]
        ];
    }
}
