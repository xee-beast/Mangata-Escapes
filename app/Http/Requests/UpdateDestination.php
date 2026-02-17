<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDestination extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:50', Rule::unique('destinations')->where(function ($query) {
                return $query->where('name', $this->name);
            })->ignore($this->route('destination'))],
            'airports' => 'required:array',
            'airports.*' => 'required:string',
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
