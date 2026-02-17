<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDestination extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:50',
                Rule::unique('destinations')->where(function ($query) {
                    return $query->where('name', $this->name);
                })
            ],
            'airports' => 'required',
            'country' => 'required|integer|exclude_if:country,0|exists:countries,id',
            'otherCountry' => [Rule::requiredIf($this->input('country') === 0) , 'string'],
            'weatherDescription' => 'nullable|string',
            'outletAdapter' => 'nullable|boolean',
            'taxDescription' => 'nullable|string|max:10000',
            'languageDescription' => 'nullable|string|max:255',
            'currencyDescription' => 'nullable|string|max:255',
            'image' => 'nullable|array',
            'image.uuid' => 'required_with:image|uuid',
            'image.path' => 'required_with:image|string',
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
            'name.unique' => 'This destination already exists with the provided airport code.',
            'airports.required' => 'You must enter at least one airport',
        ];
    }
}
