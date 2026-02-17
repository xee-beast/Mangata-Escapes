<?php

namespace App\Http\Requests\Couples;

use Carbon\Carbon;
use Freelancehunt\Validators\CreditCard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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
        $hotelBlock = $this->group->hotels->find($this->input('hotel'));
        $roomBlock = !is_null($hotelBlock) ? $hotelBlock->rooms->find($this->input('room')) : null;

        $validation = [
            'hotel' => ['required', 'integer', 'bail', function ($attribute, $value, $fail) use ($hotelBlock) { if (is_null($hotelBlock)) { $fail('The selected hotel is invalid.'); } }],
            'room' => ['required', 'integer', 'bail', function ($attribute, $value, $fail) use ($roomBlock) { if (is_null($roomBlock)) { $fail('The selected room is invalid.'); } }],
            'bed' => ['required', 'string', Rule::in((!is_null($roomBlock) ? $roomBlock->room->beds : []))],
            'beddingAgreement' => ['required', 'boolean', function ($attribute, $value, $fail) { if(!$value) { $fail('You must agree with the conditions.'); } }],
            'checkIn' => ['required', 'date', 'bail', 'before:checkOut'],
            'checkOut' => ['required', 'date', 'bail', 'after:checkIn', function ($attribute, $value, $fail) { if (Carbon::parse($this->input('checkIn'))->diffInDays($value) < $this->group->min_nights) { $fail("You must book for a minimum of {$this->group->min_nights} nights."); } }],
            'totalGuests' => [
                'required', 'integer', 'min:1', 'bail',
                function ($attribute, $value, $fail) use ($roomBlock) { if (!is_null($roomBlock) && $value > $roomBlock->room->max_occupants) { $fail('This room has a maximum capacity of ' . $roomBlock->room->max_occupants . ' ' . Str::plural('guest', $roomBlock->room->max_occupants) . '.'); } }
            ],
            'specialRequests' => ['nullable', 'string'],
            'clients' => ['required', 'array', 'min:1'],
            'clients.*.firstName' => ['required', 'string', 'max:50'],
            'clients.*.lastName' => ['required', 'string', 'max:50'],
            'clients.*.email' => ['required', 'distinct', 'email:rfc,dns'],
            'clients.*.phone' => ['required', 'numeric', 'min:1', 'digits_between:7,12'],
            'hasSeperateClients' => ['required_if:group.disable_invoice_splitting,false', 'boolean'],
        ];

        if ($this->route('step') > 1) {
            $max_child_rate = $roomBlock ? $roomBlock->child_rates->sortByDesc('to')->first() : null;
            $max_child_age = $max_child_rate ? $max_child_rate->to : 17;

            $validation = array_merge($validation, [
                'guests' => ['required', 'array'],
                'guests.*.firstName' => ['required', 'string', 'max:50'],
                'guests.*.lastName' => ['required', 'string', 'max:50'],
                'guests.0.birthDate' => ['required', 'date', 'before:' . $this->group->event_date->format('Y-m-d'), function ($attribute, $value, $fail) use ($max_child_age) {
                    if (Carbon::parse($value)->diffInYears($this->input('checkIn')) <= $max_child_age) {
                        $fail('Guest 1 must be an adult.');
                    }
                }],
                'guests.*.birthDate' => ['required', 'date', 'before:' . $this->group->event_date->format('Y-m-d'), function ($attribute, $value, $fail) use ($roomBlock, $max_child_age) {
                    if (isset($roomBlock) && $roomBlock->room->adults_only && Carbon::parse($value)->diffInYears($this->input('checkIn')) <= $max_child_age) {
                        $fail('This room is for adults only.');
                    } elseif (isset($roomBlock) && !$roomBlock->room->adults_only && Carbon::parse($value)->diffInYears($this->input('checkIn')) > $max_child_age && count(array_filter($this->input('guests'), function ($guest) use ($max_child_age) {
                            return  Carbon::parse($guest['birthDate'])->diffInYears($this->input('checkIn')) > $max_child_age;
                        })) > $roomBlock->room->max_adults) {
                            $fail('This room allows a maximum of ' . $roomBlock->room->max_adults . ' adults.');
                    } elseif (isset($roomBlock) && !$roomBlock->room->adults_only && Carbon::parse($value)->diffInYears($this->input('checkIn')) <= $max_child_age && count(array_filter($this->input('guests'), function ($guest) use ($max_child_age) {
                            return  Carbon::parse($guest['birthDate'])->diffInYears($this->input('checkIn')) <= $max_child_age;
                        })) > $roomBlock->room->max_children) {
                            $fail('This room allows a maximum of ' . $roomBlock->room->max_children . ' children.');
                    }
                }],
                'guests.*.gender' => ['required', 'in:M,F'],
                'guests.*.client' => ['exclude_unless:hasSeperateClients,true', 'required', 'in_array:clients.*.email'],
            ]);
        }

        if ($this->route('step') > 2 && !$this->group->is_fit) {
            $creditCard = CreditCard::validCreditCard($this->input('card.number'));

            $validation = array_merge($validation, [
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

        if ($this->route('step') > 3) {
            $validation = array_merge($validation, [
                'transportation' => ['required', 'boolean'],
                'insurance' => ['required', 'boolean'],
                'insuranceSignature' => ['required', 'string', function ($attribute, $value, $fail) { if (strtolower($value) !== (strtolower($this->input('clients.0.firstName') . ' ' . $this->input('clients.0.lastName')))) { $fail('You must type your full name.'); } }],
            ]);

            if (!$this->group->is_fit) {
                $validation = array_merge($validation, [
                    'deposit' => ['required', 'numeric'],
                    'cardConfirmation' => ['required', 'accepted'],
                    'cardSignature' => ['required', 'string', function ($attribute, $value, $fail) { if (strtolower($value) !== (strtolower($this->input('clients.0.firstName') . ' ' . $this->input('clients.0.lastName')))) { $fail('You must type your full name.'); } }],
                ]);
            }

            if(false == $this->input('transportation')) {
                $validation = array_merge($validation, [
                    'declinedTransportation' => ['required', 'boolean', function ($attribute, $value, $fail) { if(!$value) { $fail('You must agree with this statement.'); } }],
                ]);
            }

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
            'address.zipCode' => 'zip/postal code',
            'insuranceSignature' => 'travel insurance signature',
            'deposit' => 'payment amount',
            'cardConfirmation' => 'payment authorization',
            'cardSignature' => 'terms and conditions'
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
            'address.country.integer' => 'The country field is not valid.',
            'address.state.integer' => 'The state/province field is not valid.'
        ];
    }
}
