<?php

namespace App\Http\Requests;

use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReservation extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Couples can always update their own reservation
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            // Room arrangements validation (nested under booking)
            'booking.roomArrangements' => ['required', 'array', 'min:1'],
            'booking.roomArrangements.*.hotel' => [
                'required',
                'integer',
                Rule::exists('hotel_blocks', 'id')->where('group_id', $this->group->id)
            ],
            'booking.roomArrangements.*.room' => [
                'required',
                'integer',
                Rule::exists('room_blocks', 'id')->where('hotel_block_id', $this->input('booking.roomArrangements.*.hotel'))
            ],
            'booking.roomArrangements.*.bed' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $index = explode('.', $attribute)[2];
                    $roomId = $this->input("booking.roomArrangements.$index.room");

                    $beds = (\App\Models\Room::whereHas('room_blocks', function ($query) use ($roomId) {
                        $query->where('id', $roomId);
                    })->first() ?? (object)['beds' => []])->beds;

                    if (!in_array($value, $beds)) {
                        $fail("The selected bed is invalid for room ID $roomId.");
                    }
                }
            ],
            'booking.roomArrangements.*.beddingAgreement' => [
                'required',
                'boolean',
                function ($attribute, $value, $fail) {
                    if (!$value) {
                        $fail('You must agree with the conditions.');
                    }
                }
            ],
            'booking.roomArrangements.*.dates' => ['required', 'array'],
            'booking.roomArrangements.*.dates.start' => ['required', 'date', 'before:booking.roomArrangements.*.dates.end'],
            'booking.roomArrangements.*.dates.end' => ['required', 'date', 'after:booking.roomArrangements.*.dates.start'],
            'booking.specialRequests' => 'nullable|string',
            // Guests validation
            'guests' => 'required|array|min:1',
            'guests.*.id' => 'nullable|integer|min:1',
            'guests.*.firstName' => 'required|string|max:50',
            'guests.*.lastName' => 'required|string|max:50',
            'guests.*.gender' => ['required', Rule::in(['M', 'F'])],
            'guests.*.dates.start' => 'required|date|before:guests.*.dates.end',
            'guests.*.dates.end' => 'required|date|after:guests.*.dates.start',
            'guests.*.insurance' => 'required|boolean',
            'guests.*.client' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (is_numeric($value)) {
                        $exists = \App\Models\BookingClient::where('id', $value)
                            ->where('booking_id', $this->input('booking.id'))
                            ->exists();

                        if (!$exists) {
                            $fail('The selected client is invalid.');
                        }
                    }
                }
            ],
            'guests.*.birthDate' => ['required', 'date', 'before:' . $this->group->event_date->format('Y-m-d')],
            'guests.*.deleted_at' => 'nullable|boolean',
            'guests.*.declinedInsuranceAgreements.first' => [
                'required_if:guests.*.insurance,false',
                function ($attribute, $value, $fail) {
                    $index = explode('.', $attribute)[1];
                    $guest = $this->input("guests.$index");
                    if (isset($guest['insurance']) && $guest['insurance'] === false && !$value) {
                        $fail('You must agree with the conditions.');
                    }
                }
            ],
            'guests.*.declinedInsuranceAgreements.second' => [
                'required_if:guests.*.insurance,false',
                function ($attribute, $value, $fail) {
                    $index = explode('.', $attribute)[1];
                    $guest = $this->input("guests.$index");
                    if (isset($guest['insurance']) && $guest['insurance'] === false && !$value) {
                        $fail('You must agree with the conditions.');
                    }
                }
            ],
            'guests.*.declinedInsuranceAgreements.third' => [
                'required_if:guests.*.insurance,false',
                function ($attribute, $value, $fail) {
                    $index = explode('.', $attribute)[1];
                    $guest = $this->input("guests.$index");
                    if (isset($guest['insurance']) && $guest['insurance'] === false && !$value) {
                        $fail('You must agree with the conditions.');
                    }
                }
            ],
            'guests.*.declinedInsuranceAgreements.fourth' => [
                'required_if:guests.*.insurance,false',
                function ($attribute, $value, $fail) {
                    $index = explode('.', $attribute)[1];
                    $guest = $this->input("guests.$index");
                    if (isset($guest['insurance']) && $guest['insurance'] === false && !$value) {
                        $fail('You must agree with the conditions.');
                    }
                }
            ],

            // Optional warnings handling
            'ignoreGuestError' => 'nullable|boolean',
        ];

        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
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
            'guests.*.insurance' => 'travel insurance',
            'booking.roomArrangements.*.hotel' => 'hotel',
            'booking.roomArrangements.*.room' => 'room',
            'booking.roomArrangements.*.bed' => 'bed type',
            'booking.roomArrangements.*.dates.start' => 'check in date',
            'booking.roomArrangements.*.dates.end' => 'check out date',
        ];
    }
}
