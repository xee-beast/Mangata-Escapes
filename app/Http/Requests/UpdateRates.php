<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRates extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'minAdultsPerChild' => 'nullable|integer|min:1',
            'maxChildrenPerAdult' => 'nullable|integer|min:1',
        ];

        if (!$this->group->is_fit) {
            $rules = array_merge($rules, [
                'hasSplitDates' => 'nullable|boolean',
                'splitDate' => 'exclude_unless:hasSplitDates,true|required|date|after:' . $this->roomBlock->start_date . '|before:' . $this->roomBlock->end_date,
                'rates' => 'required|array|min:1',
                'rates.*.rate' => 'required|numeric|min:0',
                'rates.*.providerRate' => 'nullable|numeric|min:0',
                'rates.*.splitRate' => 'exclude_unless:hasSplitDates,true|required|numeric|min:0',
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
