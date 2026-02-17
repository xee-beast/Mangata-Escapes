<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGroup extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'destination' => 'required|exists:destinations,id',
            'weddingLocation' => ['required', Rule::in('resort', 'venue')],
            'venueName' => ['nullable', 'required_if:weddingLocation,venue', 'string', 'max:255'],
            'eventDate' => 'required|date',
            'brideFirstName' => 'required|string|max:25',
            'brideLastName' => 'required|string|max:25',
            'groomFirstName' => 'required|string|max:25',
            'groomLastName' => 'required|string|max:25',
            'email' => 'required|email',
            'secondaryEmail' => 'nullable|email|different:email',
            'password' => 'required|string|min:8|max:25',
            'slug' => ['required', 'alpha_dash', 'max:50', Rule::unique('groups', 'slug')->ignore($this->route('group'))],
            'isActive' => 'required|boolean',
            'couplesSitePassword' => 'nullable|string|min:8|max:25',
            'image' => 'nullable|array',
            'image.uuid' => 'required_with:image|uuid',
            'image.path' => 'required_with:image|string',
            'message' => 'nullable|string',
            'agent' => 'required|exists:travel_agents,id',
            'provider' => 'required|exists:providers,id',
            'providerId' => 'required|string',
            'insuranceRate' => ['required', Rule::exists('insurance_rates', 'id')->where('provider_id', $this->provider)],
            'useFallbackInsurance' => ['required', 'boolean'],
            'transportation' => ['required', 'boolean'],
            'minNights' => ['required', 'numeric', 'min:1'],
            'deposit' => 'required|numeric|min:0',
            'depositType' => ['required', Rule::in($this->fit ? ['fixed', 'percentage'] : ['fixed', 'percentage', 'nights'])],
            'changeFeeAmount' => 'required|numeric|min:0',
            'changeFeeDate' => 'required|date|before:eventDate',
            'notes' => 'nullable|string',
            'bannerMessage' => 'nullable|string',
            'staffMessage' => 'nullable|string',
            'disableInvoiceSplitting' => 'nullable|boolean',
        ];

        if ($this->input('transportation')) {
            $rules = array_merge($rules, [
                'airports' => 'required|array|min:1',
                'airports.*.airport' => [
                    function($attribute, $value, $fail) {
                        if($value == 0) {
                            $fail('You must select at least one airport.');
                        }
    
                        $airports = array_map(function ($airport) { return $airport['airport']; }, $this->input('airports', []));
    
                        if(count(array_unique($airports)) != count($airports)) {
                            $fail('You cannot select the same airport twice.');
                        }                        
                    }
                ],
                'airports.*.transportationRate' => 'required|numeric|min:0', 
                'airports.*.singleTransportationRate' => 'required|numeric|min:0', 
                'airports.*.oneWayTransportationRate' => 'required|numeric|min:0', 
                'airports.*.default' => [
                    function($attribute, $value, $fail) {
                        $defaultAirports = 0;

                        foreach($this->input('airports', []) as $airport) {
                            if($airport['default']) $defaultAirports++;
                        }

                        if($defaultAirports == 0) {
                            $fail('You must select a default airport.');
                        }                          

                        if($defaultAirports > 1) {
                            $fail('You cannot have more than one default airport.');
                        }                        
                    }
                ],
                'transportationType' => ['required', Rule::in('private', 'shared')],
                'transportationSubmitBefore' => ['required', 'date'],
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
            'airports.*.airport' => 'airport',
            'airports.*.transportationRate' => 'Transportation Rate', 
            'airports.*.singleTransportationRate' => 'Transportation Rate (Single)', 
            'airports.*.oneWayTransportationRate' => 'Transportation Rate (One Way)', 
        ];
    }     
}
