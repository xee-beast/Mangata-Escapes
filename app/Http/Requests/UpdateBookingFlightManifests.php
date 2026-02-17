<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookingFlightManifests extends FormRequest
{
    /**
    * Determine if the user is authorized to make this request.
    *
    * @return bool
    */
    public function authorize()
    {
        return $this->user()->can('update', $this->booking) || $this->user()->can('update', $this->individual_booking);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $validation =  [
            'flightManifests' => ['required', 'array', 'min:1'],
            'flightManifests.*.phoneNumber' => ['exclude_if:flightManifests.*.set,false', 'required', 'string', 'max:20'],
            'flightManifests.*.guestId' => ['required', Rule::exists('guests', 'id')],
            'flightManifests.*.set' => ['required', 'boolean'],
            'flightManifests.*.arrivalDepartureAirportIata' => ['nullable', 'exclude_if:flightManifests.*.set,false', 'alpha', 'size:3', 'required_if:flightManifests.*.transportationType,1,2'],
            'flightManifests.*.arrivalDepartureDate' => ['nullable', 'exclude_if:flightManifests.*.set,false', 'date', 'required_if:flightManifests.*.transportationType,1,2'],
            'flightManifests.*.arrivalAirport' => ['nullable', 'exclude_if:flightManifests.*.set,false', 'required_if:flightManifests.*.transportationType,1,2'],
            'flightManifests.*.arrivalAirline' => ['nullable', 'exclude_if:flightManifests.*.set,false', 'string', 'required_if:flightManifests.*.transportationType,1,2'],
            'flightManifests.*.arrivalNumber' => ['nullable', 'exclude_if:flightManifests.*.set,false', 'numeric', 'digits_between:1,4', 'required_if:flightManifests.*.transportationType,1,2'],
            'flightManifests.*.arrivalDateTime' => ['nullable', 'exclude_if:flightManifests.*.set,false', 'date', 'required_if:flightManifests.*.transportationType,1,2'],
            'flightManifests.*.departureAirport' => ['nullable', 'exclude_if:flightManifests.*.set,false', 'required_if:flightManifests.*.transportationType,1,3'],
            'flightManifests.*.departureDate' => ['nullable', 'exclude_if:flightManifests.*.set,false', 'date', 'required_if:flightManifests.*.transportationType,1,3'],
            'flightManifests.*.departureAirline' => ['nullable', 'exclude_if:flightManifests.*.set,false', 'string', 'required_if:flightManifests.*.transportationType,1,3'],
            'flightManifests.*.departureNumber' => ['nullable', 'exclude_if:flightManifests.*.set,false', 'numeric', 'digits_between:1,4', 'required_if:flightManifests.*.transportationType,1,3'],
            'flightManifests.*.departureDateTime' => ['nullable', 'exclude_if:flightManifests.*.set,false', 'date', 'required_if:flightManifests.*.transportationType,1,3'],
        ];

        return $validation;
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'flightManifests.*.phoneNumber' => 'phone number',
            'flightManifests.*.arrivalDepartureAirportIata' => 'departure airport',
            'flightManifests.*.arrivalDepartureDate' => 'departure date',
            'flightManifests.*.arrivalAirport' => 'arrival airport',
            'flightManifests.*.arrivalAirline' => 'arrival airline',
            'flightManifests.*.arrivalNumber' => 'arrival flight number',
            'flightManifests.*.arrivalDateTime' => 'arrival date & time',
            'flightManifests.*.departureAirport' => 'departure airport',
            'flightManifests.*.departureDate' => 'departure date',
            'flightManifests.*.departureAirline' => 'departure airline',
            'flightManifests.*.departureNumber' => 'departure flight number',
            'flightManifests.*.departureDateTime' => 'departure date & time',
            'flightManifests.*.transportationType' => 'transportation type',
        ];
    }
}
