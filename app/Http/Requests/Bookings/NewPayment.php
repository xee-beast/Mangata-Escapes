<?php

namespace App\Http\Requests\Bookings;

use App\Models\Client;
use Freelancehunt\Validators\CreditCard;
use Illuminate\Foundation\Http\FormRequest;

class NewPayment extends FormRequest
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
        $client = Client::where('email', $this->input('booking.email'))->first();

        $validation = [
            'booking.email' => ['required', 'email', 'bail', function ($attribute, $value, $fail) use ($client) { if (is_null($client)) { $fail('This email does not exist in our records.'); } }],
            'booking.code' => ['required', 'alpha_num', 'size:6', 'bail', function ($attributes, $value, $fail) use ($client) { if (!is_null($client) && !$client->bookings()->whereHas('booking', function ($query) { $query->whereNull('group_id'); })->where('reservation_code', $value)->exists()) { $fail('The booking reservation code is not valid.'); } }]
        ];

        if ($this->route('step') > 1) {
            $bookingClient = $client->bookings()->where('reservation_code', $this->input('booking.code'))->first();
            $creditCard = CreditCard::validCreditCard($this->input('card.number'));
            $cardHolderName = $this->input('useCardOnFile') ? $bookingClient->card->name : $this->input('card.name');

            if (false === $this->input('insurance.accept')) {
                $validation = array_merge($validation, [
                    'insurance.declinedInsuranceAgreements.first' => ['required', 'boolean', function ($attribute, $value, $fail) { if(!$value) { $fail('You must agree with the conditions.'); } }],
                    'insurance.declinedInsuranceAgreements.second' => ['required', 'boolean', function ($attribute, $value, $fail) { if(!$value) { $fail('You must agree with the conditions.'); } }],
                    'insurance.declinedInsuranceAgreements.third' => ['required', 'boolean', function ($attribute, $value, $fail) { if(!$value) { $fail('You must agree with the conditions.'); } }],
                    'insurance.declinedInsuranceAgreements.fourth' => ['required', 'boolean', function ($attribute, $value, $fail) { if(!$value) { $fail('You must agree with the conditions.'); } }],
                ]);
            }

            $validation = array_merge($validation, [
                'amount' => ['required', 'numeric', 'min:0'],
                'useCardOnFile' => ['required', 'boolean', 'bail', function ($attribute, $value, $fail) use ($bookingClient) { if ($value && !$bookingClient->card()->exists()) { $fail('There is no card on file registered for this booking, you must enter your payment information below.'); } }],
                'confirmation.accept' => ['required', function ($attribute, $value, $fail) { if (!$value) { $fail('The payment authorization must be accepted.'); } }],
                'confirmation.signature' => [
                    'required',
                    function ($attribute, $value, $fail) use ($cardHolderName) {
                        if (!$cardHolderName || strtolower($value) !== strtolower($cardHolderName)) {
                            $fail('You must type the full name associated with the selected card.');
                        }
                    }
                ]
            ]);

            if (is_null($bookingClient->insurance)) {
                $validation = array_merge($validation, [
                    'insurance.accept' => ['required', 'boolean',],
                    'insurance.signature' => ['required', function ($attribute, $value, $fail) use ($bookingClient) { if ((strtolower($value) !== strtolower($bookingClient->name))) { $fail('You must type your full name.'); } }]
                ]);
            }

            if (!$this->input('useCardOnFile', true)) {
                $validation = array_merge($validation, [
                    'updateCardOnFile' => ['sometimes', 'boolean'],
                    'card.name' => ['required', 'string', 'max:100'],
                    'card.type' => ['required', function ($attribute, $value, $fail) use ($creditCard) { if ($value != $creditCard['type']) { $fail('The credit card type does not match the number.'); } }],
                    'card.number' => [
                        'required', 
                        function ($attribute, $value, $fail) use ($creditCard) { 
                            if (!$creditCard['valid']) { 
                                $fail('The card number is not valid.'); 
                            } else if (!in_array($creditCard['type'], ['visa', 'mastercard', 'amex', 'discover'])) { 
                                $fail('The card must be a Visa, Mastercard, American Express or Discover card.'); 
                            } 
                        }
                    ],
                    'card.expMonth' => ['required', 'digits:2', function ($attribute, $value, $fail) { if (!CreditCard::validDate($this->input('card.expYear'), $value)) { $fail('The expiration date is not valid.'); } }],
                    'card.expYear' => ['required', 'digits:4', function ($attribute, $value, $fail) { if (!CreditCard::validDate($value, $this->input('card.expMonth'),)) { $fail('The expiration date is not valid.'); } }],
                    'card.code' => [
                        'required',
                        function ($attribute, $value, $fail) use ($creditCard) { if ( !CreditCard::validCvc($value, $creditCard['type']) ) { $fail('The code in not valid for this card type.'); } },
                        function ($attribute, $value, $fail) {
                            $cardType = request('card.type');
                            $cvvLength = strlen($value);
    
                            if ($cardType === 'amex' && $cvvLength !== 4) {
                                $fail(ucfirst($cardType) . ' requires a 4-digit CID.');
                            } elseif (in_array($cardType, ['visa', 'mastercard', 'discover']) && $cvvLength !== 3) {
                                $fail(ucfirst($cardType) . ' requires a 3-digit CVV.');
                            }
                        }
                    ],
                    'address.city' => ['required', 'string', 'max:50'],
                    'address.line1' => ['required', 'string', 'max:200'],
                    'address.line2' => ['nullable', 'string', 'max:200'],
                    'address.zipCode' => ['required', 'string', 'min:3', 'max:20']
                ]);
    
                $address = [
                    'address.city' => ['required', 'string', 'max:50'],
                    'address.line1' => ['required', 'string', 'max:200'],
                    'address.line2' => ['nullable', 'string', 'max:200'],
                    'address.zipCode' => ['required', 'string', 'min:3', 'max:20']
                ];

                if ($this->input('address.country') === '0') {
                    $address = array_merge($address, [
                        'address.otherCountry' => ['required', 'string', 'max:50'],
                        'address.otherState' => ['required', 'string', 'max:50'],
                    ]);
                } else {
                    $address = array_merge($address, [
                        'address.country' => ['required', 'integer', 'bail', 'exists:countries,id'],
                        'address.state' => ['required', 'integer', 'bail', 'exists:states,id'],
                    ]);
                }

                $validation = array_merge($validation, $address);
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
            'amount' => 'payment amount',
            'booking.email' => 'email',
            'booking.code' => 'booking reservation code',
            'confirmation.accept' => 'payment authorization',
            'confirmation.signature' => 'payment authorization signature',
            'insurance.accept' => 'travel insurance',
            'insurance.signature' => 'travel insurance signature',
            'card.name' => 'cardholder name',
            'card.number' => 'card number',
            'card.type' => 'card type',
            'card.expMonth' => 'expiration month',
            'card.expYear' => 'expiration year',
            'card.code' => 'code',
            'address.country' => 'country',
            'address.otherCountry' => 'country',
            'address.state' => 'state/province',
            'address.otherState' => 'state/province',
            'address.city' => 'city',
            'address.line1' => 'address line 1',
            'address.line2' => 'address line 2',
            'address.zipCode' => 'zip/postal code'
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
            'booking.code.alpha_num' => 'The code is not valid.',
            'booking.code.size' => 'The code is not valid.',
            'address.country.integer' => 'The country field is not valid.',
            'address.state.integer' => 'The state/province field is not valid.'
        ];
    }
}
