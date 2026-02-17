<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccomodation extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'hotel' => ['required', 'bail', Rule::exists('hotels', 'id')->where('destination_id', $this->group->destination_id)],
            'room' => ['required', 'bail', Rule::exists('rooms', 'id')->where('hotel_id', $this->hotel)],
            'minAdultsPerChild' => 'nullable|integer|min:1',
            'maxChildrenPerAdult' => 'nullable|integer|min:1',
        ];

        if (!$this->group->is_fit) {
            $rules = array_merge($rules, [
                'inventory' => 'nullable|integer|min:1',
                'dates.start' => 'required|date|before:dates.end|before:' . $this->group->event_date,
                'dates.end' => 'required|date|after:dates.start|after:' . $this->group->event_date,
                'hasSplitDates' => 'nullable|boolean',
                'splitDate' => 'exclude_unless:hasSplitDates,true|required|date|after:dates.start|before:dates.end',
                'rates' => 'required|array|min:1',
                'rates.*.rate' => 'required|numeric|min:0',
                'rates.*.providerRate' => 'nullable|numeric|min:0',
                'rates.*.splitRate' => 'exclude_unless:hasSplitDates,true|required|required|numeric|min:0',
                'rates.*.splitProviderRate' => 'exclude_unless:hasSplitDates,true|nullable|numeric|min:0',
                'childRates.*.from' => 'required|numeric|between:0,16',
                'childRates.*.to' => 'required|numeric|between:1,17',
                'childRates.*.rate' => 'required|numeric|min:0',
                'childRates.*.providerRate' => 'nullable|numeric|min:0',
                'childRates.*.splitRate' => 'exclude_unless:hasSplitDates,true|required|numeric|min:0',
                'childRates.*.splitProviderRate' => 'exclude_unless:hasSplitDates,true|nullable|numeric|min:0',
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
            'dates.start' => 'block dates',
            'dates.end' => 'block dates',
            'rates.*.rate' => 'rate',
            'rates.*.providerRate' => 'provider rate',
            'rates.*.splitRate' => 'rate',
            'rates.*.splitProviderRate' => 'provider rate',
            'childRates.*.from' => 'from age',
            'childRates.*.to' => 'to age',
            'childRates.*.rate' => 'rate',
            'childRates.*.providerRate' => 'provider rate',
            'childRates.*.splitRate' => 'rate',
            'childRates.*.splitProviderRate' => 'provider rate',
            'minAdultsPerChild' => 'minimum adults',
            'maxChildrenPerAdult' => 'maximum children',
        ];
    }
}
