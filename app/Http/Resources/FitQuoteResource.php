<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class FitQuoteResource extends JsonResource
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
            'bookingClientId' => $this->booking_client_id,
            'expiryDateTime' => $this->expiry_date_time ? Carbon::parse($this->expiry_date_time)->setTimezone('America/New_York')->format('m-d-Y h:i A T') : null,
            'acceptedAt' => $this->accepted_at ? Carbon::parse($this->accepted_at)->setTimezone('America/New_York')->format('m-d-Y h:i A T') : null,
            'isCancelled' => $this->is_cancelled,
        ];
    }
}
