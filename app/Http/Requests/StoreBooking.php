<?php

namespace App\Http\Requests;

use App\Models\Room;
use App\Models\RoomBlock;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBooking extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $roomBlock = RoomBlock::find($this->room);

        $rules = [
            'hotel' => ['required', 'bail', Rule::exists('hotel_blocks', 'id')->where('group_id', $this->group->id)],
            'room' => ['required', 'bail', Rule::exists('room_blocks', 'id')->where('hotel_block_id', $this->hotel)],
            'bed' => [
                'required',
                'bail',
                Rule::in((Room::whereHas('room_blocks', function ($query) {
                    $query->where('id', $this->room);
                })->first() ?? (object)['beds' => []])->beds)
            ],
            'dates.start' => 'required|date|before:dates.end',
            'dates.end' => 'required|date|after:dates.start',
            'specialRequests' => 'nullable|string',
            'notes' => 'nullable|string',
            'client.firstName' => 'required|string',
            'client.lastName' => 'required|string',
            'client.email' => 'required|email',
            'client.phone' => 'required|numeric|digits_between:7,12',
            'guests.*.firstName' => 'required|string|max:50',
            'guests.*.lastName' => 'required|string|max:50',
            'guests.*.gender' => ['required', Rule::in(['M', 'F'])],
            'insurance' => 'required|boolean',
        ];

        if (!$this->group->is_fit) {
            $rules = array_merge($rules, [
                'payment' => 'required|numeric',
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
                'address.city' => 'required|string|max:50',
                'address.line1' => 'required|string',
                'address.line2' => 'nullable|string',
                'address.zipCode' => 'required|string|max:20',
            ]);

            if ($this->input('address.country') === 0) {
                $rules = array_merge($rules, [
                    'address.otherCountry' => 'required|string|max:50',
                    'address.otherState' => 'required|string|max:50',
                ]);
            } else {
                $rules = array_merge($rules, [
                    'address.country' => 'required|exists:countries,id',
                    'address.state' => 'required|exists:states,id',
                ]);
            }
        }

        $max_child_rate = $roomBlock ? $roomBlock->child_rates->sortByDesc('to')->first() : null;
        $max_child_age = $max_child_rate ? $max_child_rate->to : 17;

        $rules = array_merge($rules, [  
            'guests.0.birthDate' => ['required', 'date', 'before:' . $this->group->event_date->format('Y-m-d'), function ($attribute, $value, $fail) use ($max_child_age) {
                if (Carbon::parse($value)->diffInYears($this->input('dates.start')) <= $max_child_age) {
                    $fail('Guest 1 must be an adult.');
                }
            }],
            'guests.*.birthDate' => ['required', 'date', 'before:' . $this->group->event_date->format('Y-m-d'), function ($attribute, $value, $fail) use ($roomBlock, $max_child_age) {
                if (isset($roomBlock) && $roomBlock->room->adults_only && Carbon::parse($value)->diffInYears($this->input('dates.start')) <= $max_child_age) {
                    $fail('This room is for adults only.');
                } elseif (isset($roomBlock) && !$roomBlock->room->adults_only && Carbon::parse($value)->diffInYears($this->input('dates.start')) > $max_child_age && count(array_filter($this->input('guests'), function ($guest) use ($max_child_age) {
                        return  Carbon::parse($guest['birthDate'])->diffInYears($this->input('dates.start')) > $max_child_age;
                    })) > $roomBlock->room->max_adults) {
                        $fail('This room allows a maximum of ' . $roomBlock->room->max_adults . ' adults.');
                } elseif (isset($roomBlock) && !$roomBlock->room->adults_only && Carbon::parse($value)->diffInYears($this->input('dates.start')) <= $max_child_age && count(array_filter($this->input('guests'), function ($guest) use ($max_child_age) {
                        return  Carbon::parse($guest['birthDate'])->diffInYears($this->input('dates.start')) <= $max_child_age;
                    })) > $roomBlock->room->max_children) {
                        $fail('This room allows a maximum of ' . $roomBlock->room->max_children . ' children.');
                }
            }],
        ]);

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
            'dates.start' => 'dates',
            'dates.end' => 'dates',
            'phone' => 'telephone',
            'seperateInvoiceEmails.*' => 'email',
            'client.firstName' => 'first name',
            'client.lastName' => 'last name',
            'client.email' => 'email',
            'client.phone' => 'phone number',
            'guests.*.firstName' => 'first name',
            'guests.*.lastName' => 'last name',
            'guests.*.gender' => 'gender',
            'guests.*.birthDate' => 'date of birth',
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
