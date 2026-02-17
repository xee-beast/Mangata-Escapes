<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddAirport extends FormRequest
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
        return [
            'newAirport.airport_code' => [
                    'required', 
                    'alpha',
                    'string', 
                    'min:3', 
                    'max:3',
                    Rule::notIn(array_map(function ($airport) { return $airport['airport_code']; }, $this->input('airports', []))),
                ]
            ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'newAirport.airport_code' => 'airport code',
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
            'newAirport.airport_code.not_in' => 'The airport code must be unique.'
        ];
    }    
}
