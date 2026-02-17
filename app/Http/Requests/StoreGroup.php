<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGroup extends FormRequest
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
            'fit' => 'nullable|boolean',
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
            'slug' => 'required|alpha_dash|max:50|unique:groups,slug',
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
            'minNights' => ['required', 'numeric', 'min:1'],
            'deposit' => 'required|numeric|min:0',
            'depositType' => ['required', Rule::in($this->fit ? ['fixed', 'percentage'] : ['fixed', 'percentage', 'nights'])],
            'changeFeeAmount' => 'required|numeric|min:0',
            'changeFeeDate' => 'required|date|before:eventDate',
            'dueDate' => 'required|date|before:eventDate',
            'cancellationDate' => 'required|date|before:eventDate',
            'notes' => 'nullable|string',
            'disableInvoiceSplitting' => 'nullable|boolean',
        ];

        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [];
    }  
}