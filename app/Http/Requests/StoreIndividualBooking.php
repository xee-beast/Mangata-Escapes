<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIndividualBooking extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'hotelAssistance' => 'required|boolean',
            'hotelPreferences' => 'required_if:hotelAssistance,true|nullable|string|max:5000',
            'hotelName' => 'required_if:hotelAssistance,false|nullable|string|max:255',
            'roomCategory' => 'required|boolean',
            'roomCategoryName' => 'required_if:roomCategory,true|nullable|string|max:255',
            'dates.start' => 'required|date|before:dates.end',
            'dates.end' => 'required|date|after:dates.start',
            'specialRequests' => 'nullable|string|max:5000',
            'notes' => 'nullable|string|max:5000',
            'budget' => 'nullable|numeric|min:0',
            'client.firstName' => 'required|string|max:50',
            'client.lastName' => 'required|string|max:50',
            'client.email' => 'required|email|max:255',
            'client.phone' => 'required|numeric|digits_between:7,12',
            'guests' => 'required|array|min:1',
            'guests.*.firstName' => 'required|string|max:50',
            'guests.*.lastName' => 'required|string|max:50',
            'guests.*.gender' => 'required|in:M,F',
            'guests.0.birthDate' => ['required', 'date', 'before:dates.start', function ($attribute, $value, $fail) {
                if (Carbon::parse($value)->diffInYears($this->input('dates.start')) <= 17) {
                    $fail('Guest 1 must be an adult.');
                }
            }],
            'guests.*.birthDate' => 'required|date|before:dates.start',
            'transportation' => 'required|boolean',
            'departureGateway' => 'nullable|string|max:255',
            'flightPreferences' => 'nullable|string|max:5000',
            'airlineMembershipNumber' => 'nullable|string|max:255',
            'knownTravelerNumber' => 'nullable|string|max:255',
            'flightMessage' => 'nullable|string|max:255',
            'insurance' => 'required|boolean',
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
            'dates.start' => 'travel dates',
            'dates.end' => 'travel dates',
            'client.firstName' => 'first name',
            'client.lastName' => 'last name',
            'client.email' => 'email',
            'client.phone' => 'phone number',
            'guests.*.firstName' => 'first name',
            'guests.*.lastName' => 'last name',
            'guests.*.gender' => 'gender',
            'guests.*.birthDate' => 'date of birth',
        ];
    }
}
