<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookingClient extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $booking = $this->route('booking') ?? $this->route('individual_booking');

        $rules = [
            'firstName' => 'required|string',
            'lastName' => 'required|string',
            'email' => ['required', 'email', Rule::notIn($booking->clients()->with('client')->get()->pluck('client.email'))],
            'phone' => 'nullable|digits_between:7,12',
            'hasPaymentInfo' => 'nullable|boolean',
            'card.name' => 'exclude_unless:hasPaymentInfo,true|required|string|max:100',
            'card.number' => 'exclude_unless:hasPaymentInfo,true|required|digits_between:15,16',
            'card.type' => ['exclude_unless:hasPaymentInfo,true', 'required', Rule::in(['visa', 'mastercard', 'amex', 'discover'])],
            'card.expMonth' => ['exclude_unless:hasPaymentInfo,true', 'required', 'digits:2', Rule::in(['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'])],
            'card.expYear' => 'exclude_unless:hasPaymentInfo,true|required|numeric|min:' . now()->year,
            'card.code' => [
                'exclude_unless:hasPaymentInfo,true',
                'required',
                function ($attribute, $value, $fail) {
                    $cardType = request('card.type');
                    $cvvLength = strlen($value);

                    if ($cardType === 'amex' && $cvvLength !== 4) {
                        $fail(ucfirst($cardType) . ' requires a 4-digit CVV.');
                    } elseif (in_array($cardType, ['visa', 'mastercard', 'discover']) && $cvvLength !== 3) {
                        $fail(ucfirst($cardType) . ' requires a 3-digit CVV.');
                    }
                }
            ],
            'address.city' => 'exclude_unless:hasPaymentInfo,true|required|string|max:50',
            'address.line1' => 'exclude_unless:hasPaymentInfo,true|required|string',
            'address.line2' => 'exclude_unless:hasPaymentInfo,true|nullable|string',
            'address.zipCode' => 'exclude_unless:hasPaymentInfo,true|required|string|max:20',
        ];

        if ($this->input('address.country') === 0) {
            $rules = array_merge($rules, [
                'address.otherCountry' => 'exclude_unless:hasPaymentInfo,true|required|string|max:50',
                'address.otherState' => 'exclude_unless:hasPaymentInfo,true|required|string|max:50',
            ]);
        } else {
            $rules = array_merge($rules, [
                'address.country' => 'exclude_unless:hasPaymentInfo,true|required|exists:countries,id',
                'address.state' => 'exclude_unless:hasPaymentInfo,true|required|exists:states,id',
            ]);
        }

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
            'phone' => 'telephone',
            'card.name' => 'cardholder name',
            'card.number' => 'card number',
            'card.type' => 'card type',
            'card.expMonth' => 'card expiration month',
            'card.expYear' => 'card expiration year',
            'card.code' => 'cvv code',
            'address.country' => 'country',
            'address.hasOtherCountry' => 'other',
            'address.otherCountry' => 'other country',
            'address.state' => 'state',
            'address.otherState' => 'state',
            'address.city' => 'city',
            'address.line1' => 'address line 1',
            'address.line2' => 'address line 2',
            'address.zipCode' => 'zip code',
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
            'email.not_in' => 'The email must be unique to this booking.'
        ];
    }
}
