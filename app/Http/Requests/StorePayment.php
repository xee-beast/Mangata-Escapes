<?php

namespace App\Http\Requests;

use App\Models\BookingClient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePayment extends FormRequest
{
    /**
     * Override the validator instance for the request.
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function getValidatorInstance()
    {
        $validator = parent::getValidatorInstance();

        $validator->after(function ($validator) {
            if (
                $this->input('useExistingCard', false) &&
                !$validator->errors()->has('client') &&
                !BookingClient::where('id', $this->input('client'))->whereHas('client.cards')->exists()
            ) {
                $validator->errors()->add('useExistingCard', 'This client does not have a registered card on file.');
            }
        });

        return $validator;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $booking = $this->booking ?? $this->individual_booking;

        $rules = [
            'client' => ['required', 'bail', Rule::exists('booking_clients', 'id')->where('booking_id', $booking->id)],
            'amount' => 'required|numeric',
            'notes' => 'nullable|string',
            'useExistingCard' => 'nullable|boolean',
        ];

        if ($this->input('useExistingCard') && BookingClient::where('id', $this->input('client'))->whereHas('client.cards')->exists()) {
            $rules = array_merge($rules, [
                'existingCard' => 'required',
            ]);
        }
        
        if (!$this->input('useExistingCard', false)) {
            $rules = array_merge($rules, [
                'card.name' => 'required|string|max:100',
                'card.number' => 'required|digits_between:15,16',
                'card.type' => ['required', Rule::in(['visa', 'mastercard', 'amex', 'discover'])],
                'card.expMonth' => ['required', 'digits:2', Rule::in(['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'])],
                'card.expYear' => 'required|numeric|min:' . now()->year,
                'card.code' => [
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
                'address.country' => 'exclude_if:address.country,0|required|exists:countries,id',
                'address.otherCountry' => 'exclude_unless:address.country,0|required|string|max:50',
                'address.state' => 'exclude_if:address.country,0|required|exists:states,id',
                'address.otherState' => 'exclude_unless:address.country,0|required|string|max:50',
                'address.city' => 'required|string|max:50',
                'address.line1' => 'required|string',
                'address.line2' => 'nullable|string',
                'address.zipCode' => 'required|string|max:20',
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
}
