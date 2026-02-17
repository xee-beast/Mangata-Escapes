<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIndividualBookingGuests extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'guests' => 'required|array|min:1',
            'guests.*.id' => 'nullable|integer|min:1',
            'guests.*.firstName' => 'required|string|max:50',
            'guests.*.lastName' => 'required|string|max:50',
            'guests.*.gender' => 'required|in:M,F',
            'guests.*.dates.start' => 'required|date|before:guests.*.dates.end',
            'guests.*.dates.end' => 'required|date|after:guests.*.dates.start',
            'insurance' => 'nullable|boolean',
            'guests.*.client' => 'required|exists:booking_clients,id,booking_id,' . $this->individual_booking->id,
            'guests.*.birthDate' => 'required|date|before:' . $this->individual_booking->check_in->format('Y-m-d'),
        ];

        return $rules;
    }

    public function attributes()
    {
        return [
            'guests.*.firstName' => 'first name',
            'guests.*.lastName' => 'last name',
            'guests.*.gender' => 'gender',
            'guests.*.birthDate' => 'date of birth',
            'guests.*.dates.start' => 'check in date',
            'guests.*.dates.end' => 'check out date',
            'guests.*.client' => 'client',
        ];
    }
}
