<?php

namespace App\Http\Requests\Bookings;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class NewBooking extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $validation = [
            'hotelAssistance' => ['required', 'boolean'],
            'hotelPreferences' => ['required_if:hotelAssistance,true', 'nullable', 'string', 'max:5000'],
            'hotelName' => ['required_if:hotelAssistance,false', 'nullable', 'string', 'max:255'],
            'roomCategory' => ['required', 'boolean'],
            'roomCategoryName' => ['required_if:roomCategory,true', 'nullable', 'string', 'max:255'],
            'checkIn' => ['required', 'date', 'bail', 'before:checkOut'],
            'checkOut' => ['required', 'date', 'bail', 'after:checkIn'],
            'totalGuests' => ['required', 'integer', 'min:1'],
            'specialRequests' => ['nullable', 'string', 'max:5000'],
            'budget' => ['required', 'numeric', 'min:0'],
            'clients' => ['required', 'array', 'min:1'],
            'clients.*.firstName' => ['required', 'string', 'max:50'],
            'clients.*.lastName' => ['required', 'string', 'max:50'],
            'clients.*.email' => ['required', 'distinct', 'email:rfc,dns', 'max:255'],
            'clients.*.phone' => ['required', 'numeric', 'min:1', 'digits_between:7,12'],
            'hasSeperateClients' => ['required', 'boolean'],
        ];

        if ($this->route('step') > 1) {
            $validation = array_merge($validation, [
                'guests' => ['required', 'array', 'min:1'],
                'guests.*.firstName' => ['required', 'string', 'max:50'],
                'guests.*.lastName' => ['required', 'string', 'max:50'],
                'guests.0.birthDate' => ['required', 'date', 'before:checkIn', function ($attribute, $value, $fail) {
                    if (Carbon::parse($value)->diffInYears($this->input('checkIn')) <= 17) {
                        $fail('Guest 1 must be an adult.');
                    }
                }],
                'guests.*.birthDate' => ['required', 'date', 'before:checkIn'],
                'guests.*.gender' => ['required', 'in:M,F'],
                'guests.*.client' => ['exclude_unless:hasSeperateClients,true', 'required', 'in_array:clients.*.email'],
            ]);
        }

        if ($this->route('step') > 2) {
            $validation = array_merge($validation, [
                'transportation' => ['required', 'boolean'],
                'departureGateway' => ['required_if:transportation,true', 'nullable', 'string', 'max:255'],
                'flightPreferences' => ['required_if:transportation,true', 'nullable', 'string', 'max:5000'],
                'airlineMembershipNumber' => ['required_if:transportation,true', 'nullable', 'string', 'max:255'],
                'knownTravelerNumber' => ['required_if:transportation,true', 'nullable', 'string', 'max:255'],
                'flightMessage' => ['required_if:transportation,true', 'nullable', 'string', 'max:255'],
                'insurance' => ['required', 'boolean'],
                'insuranceSignature' => ['required', 'string', function ($attribute, $value, $fail) { if (strtolower($value) !== (strtolower($this->input('clients.0.firstName') . ' ' . $this->input('clients.0.lastName')))) { $fail('You must type your full name.'); } }],
            ]);

            if(false == $this->input('insurance')) {
                $validation = array_merge($validation, [
                    'declinedInsuranceAgreements.first' => ['required', 'boolean', function ($attribute, $value, $fail) { if(!$value) { $fail('You must agree with the conditions.'); } }],
                    'declinedInsuranceAgreements.second' => ['required', 'boolean', function ($attribute, $value, $fail) { if(!$value) { $fail('You must agree with the conditions.'); } }],
                    'declinedInsuranceAgreements.third' => ['required', 'boolean', function ($attribute, $value, $fail) { if(!$value) { $fail('You must agree with the conditions.'); } }],
                    'declinedInsuranceAgreements.fourth' => ['required', 'boolean', function ($attribute, $value, $fail) { if(!$value) { $fail('You must agree with the conditions.'); } }],
                ]);
            }
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
            'clients.*.firstName' => 'first name',
            'clients.*.lastName' => 'last name',
            'clients.*.email' => 'email',
            'clients.*.phone' => 'phone number',
            'guests.*.firstName' => 'first name',
            'guests.*.lastName' => 'last name',
            'guests.*.birthDate' => 'birth date',
            'guests.*.gender' => 'gender',
            'guests.*.client' => 'invoiced to',
            'insuranceSignature' => 'travel insurance signature',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'hotel.integer' => 'The hotel field is not valid.',
            'room.integer' => 'The room field not valid.',
            'hasSeperateClients.required' => 'You must select an option.',
            'hasSeperateClients.boolean' => 'The option you have selected is invalid.',
            'guests.*.client.in_array' => 'The option you have selected is invalid.',
        ];
    }
}
