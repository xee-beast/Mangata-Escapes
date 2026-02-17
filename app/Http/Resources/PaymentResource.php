<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
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
            'bookingClient' => new BookingClientResource($this->whenLoaded('booking_client')),
            'amount' => $this->amount,
            'notes' => $this->notes,
            'createdAt' => $this->created_at,
            'cancelledAt' => $this->cancelled_at,
            'cardDeclined' => $this->card_declined,
            'confirmedAt' => $this->confirmed_at,
            'card' => new CardResource($this->whenLoaded('card')),
            'can' => [
                'view' => auth()->user()->can('view', $this->resource),
                'update' => auth()->user()->can('update', $this->resource),
                'delete' => auth()->user()->can('delete', $this->resource),
                'forceDelete' => auth()->user()->can('forceDelete', $this->resource),
                'confirm' => auth()->user()->can('confirm', $this->resource)
            ]
        ];
    }
}
