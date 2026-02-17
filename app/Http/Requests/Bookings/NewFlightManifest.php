<?php

namespace App\Http\Requests\Bookings;

use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class NewFlightManifest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $client = Client::where('email', $this->input('booking.email'))->first();

        $validation = [
            'booking.email' => ['required', 'email', 'bail', function ($attribute, $value, $fail) use ($client) { if (is_null($client)) { $fail('This email does not exist in our records.'); } }],
            'booking.code' => ['required', 'alpha_num', 'size:6', 'bail', function ($attributes, $value, $fail) use ($client) { if (!is_null($client) && !$client->bookings()->whereHas('booking', function ($query) { $query->whereNull('group_id'); })->where('reservation_code', $value)->exists()) { $fail('The booking reservation code is not valid.'); } }]
        ];

        if ($this->route('step') > 1) {
            $disable_date = Carbon::now()->addDays(7)->toDateString();

            $validation = array_merge($validation, [
                'form.phoneNumber' => ['required', 'string', 'max:20'],
                'form.arrivalDepartureAirport' => ['nullable', 'alpha', 'size:3', 'required_if:form.arrivalDetailsRequired,true'],
                'form.arrivalDepartureDate' => ['nullable', 'date', 'required_if:form.arrivalDetailsRequired,true'],
                'form.arrivalAirport' => ['nullable', 'required_if:form.arrivalDetailsRequired,true'],
                'form.arrivalAirline' => ['nullable', 'string', 'required_if:form.arrivalDetailsRequired,true'],
                'form.arrivalNumber' => ['nullable', 'numeric', 'digits_between:1,4', 'required_if:form.arrivalDetailsRequired,true'],
                'form.arrivalDateTime' => [
                    'nullable',
                    'date',
                    'required_if:form.arrivalDetailsRequired,true',
                    function ($attribute, $value, $fail) use ($disable_date) {
                        $arrival_date = Carbon::parse($value)->toDateString();

                        if (Carbon::parse($arrival_date)->lte($disable_date)) {
                            $fail("Flight itinerary cannot be uploaded within 7 days of arrival.");
                        }
                    },
                ],
                'form.departureAirport' => ['nullable', 'required_if:form.departureDetailsRequired,true'],
                'form.departureDate' => [
                    'nullable',
                    'date',
                    'required_if:form.departureDetailsRequired,true',
                    function ($attribute, $value, $fail) use ($disable_date) {
                        if ($this->input('form.arrivalDetailsRequired')) {
                            if (Carbon::parse($value)->lte(Carbon::parse($this->input('form.arrivalDateTime'))->toDateString())) {
                                $fail('The departure date must be after the arrival date.');
                            }
                        } else {
                            if (Carbon::parse($value)->lte($disable_date)) {
                                $fail("Flight itinerary cannot be uploaded within 7 days of departure.");
                            }
                        }
                    },
                ],
                'form.departureAirline' => ['nullable', 'string', 'required_if:form.departureDetailsRequired,true'],
                'form.departureNumber' => ['nullable', 'numeric', 'digits_between:1,4', 'required_if:form.departureDetailsRequired,true'],
                'form.departureDateTime' => [
                    'nullable',
                    'date',
                    'required_if:form.departureDetailsRequired,true',
                    function ($attribute, $value, $fail) use ($disable_date) {
                        $departure_date = Carbon::parse($value)->toDateString();

                        if ($this->input('form.arrivalDetailsRequired')) {
                            if (Carbon::parse($departure_date)->lte(Carbon::parse($this->input('form.arrivalDateTime'))->toDateString())) {
                                $fail('The departure date must be after the arrival date.');
                            }
                        } else {
                            if (Carbon::parse($departure_date)->lte($disable_date)) {
                                $fail("Flight itinerary cannot be uploaded within 7 days of departure.");
                            }
                        }
                    },
                ],
                'guests' => ['required', 'array', 'min:1'],
                'guests.*.phoneNumber' => ['required', 'string', 'max:20'],
                'guests.*.arrivalDepartureAirport' => ['nullable', 'alpha', 'size:3', 'required_if:guests.*.transportationType,1,2'],
                'guests.*.arrivalDepartureDate' => ['nullable', 'date', 'required_if:guests.*.transportationType,1,2'],
                'guests.*.arrivalAirport' => ['nullable', 'required_if:guests.*.transportationType,1,2'],
                'guests.*.arrivalAirline' => ['nullable', 'string', 'required_if:guests.*.transportationType,1,2'],
                'guests.*.arrivalNumber' => ['nullable', 'numeric', 'digits_between:1,4', 'required_if:guests.*.transportationType,1,2'],
                'guests.*.arrivalDateTime' => [
                    'nullable',
                    'date',
                    'required_if:guests.*.transportationType,1,2',
                    function ($attribute, $value, $fail) use ($disable_date) {
                        $arrival_date = Carbon::parse($value)->toDateString();

                        if (Carbon::parse($arrival_date)->lte($disable_date)) {
                            $fail("Flight itinerary cannot be uploaded within 7 days of arrival.");
                        }
                    },
                ],
                'guests.*.departureAirport' => ['nullable', 'required_if:guests.*.transportationType,1,3'],
                'guests.*.departureDate' => [
                    'nullable',
                    'date',
                    'required_if:guests.*.transportationType,1,3',
                    function ($attribute, $value, $fail) use ($disable_date) {
                        if ($this->input('form.arrivalDetailsRequired')) {
                            if (Carbon::parse($value)->lte(Carbon::parse($this->input('form.arrivalDateTime'))->toDateString())) {
                                $fail('The departure date must be after the arrival date.');
                            }
                        } else {
                            if (Carbon::parse($value)->lte($disable_date)) {
                                $fail("Flight itinerary cannot be uploaded within 7 days of departure.");
                            }
                        }
                    },
                ],
                'guests.*.departureAirline' => ['nullable', 'string', 'required_if:guests.*.transportationType,1,3'],
                'guests.*.departureNumber' => ['nullable', 'numeric', 'digits_between:1,4', 'required_if:guests.*.transportationType,1,3'],
                'guests.*.departureDateTime' => [
                    'nullable',
                    'date',
                    'required_if:guests.*.transportationType,1,3',
                    function ($attribute, $value, $fail) use ($disable_date) {
                        $departure_date = Carbon::parse($value)->toDateString();

                        if ($this->input('form.arrivalDetailsRequired')) {
                            if (Carbon::parse($departure_date)->lte(Carbon::parse($this->input('form.arrivalDateTime'))->toDateString())) {
                                $fail('The departure date must be after the arrival date.');
                            }
                        } else {
                            if (Carbon::parse($departure_date)->lte($disable_date)) {
                                $fail("Flight itinerary cannot be uploaded within 7 days of departure.");
                            }
                        }
                    },
                ],
            ]);
        }

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
            'booking.email' => 'email',
            'booking.code' => 'booking reservation code',
            'guests.*.phoneNumber' => 'phone number',
            'guests.*.arrivalDepartureAirport' => 'departure airport',
            'guests.*.arrivalDepartureDate' => 'departure date',
            'guests.*.arrivalAirport' => 'arrival airport',
            'guests.*.arrivalAirline' => 'arrival airline',
            'guests.*.arrivalNumber' => 'arrival flight number',
            'guests.*.arrivalDateTime' => 'arrival date & time',
            'guests.*.departureAirport' => 'departure airport',
            'guests.*.departureDate' => 'departure date',
            'guests.*.departureAirline' => 'departure airline',
            'guests.*.departureNumber' => 'departure flight number',
            'guests.*.departureDateTime' => 'departure date & time',
            'guests.*.transportationType' => 'transportation type',
            'form.phoneNumber' => 'phone number',
            'form.arrivalDetailsRequired' => 'arrival details required',
            'form.arrivalDepartureAirport' => 'departure airport',
            'form.arrivalDepartureDate' => 'departure date',
            'form.arrivalAirport' => 'arrival airport',
            'form.arrivalAirline' => 'arrival airline',
            'form.arrivalNumber' => 'arrival flight number',
            'form.arrivalDateTime' => 'arrival date & time',
            'form.departureDetailsRequired' => 'departure details required',
            'form.departureAirport' => 'departure airport',
            'form.departureDate' => 'departure date',
            'form.departureAirline' => 'departure airline',
            'form.departureNumber' => 'departure flight number',
            'form.departureDateTime' => 'departure date & time',
            'form.transportationType' => 'transportation type',
        ];
    }
}
