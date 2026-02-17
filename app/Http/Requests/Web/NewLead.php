<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class NewLead extends FormRequest
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
            'bride.firstName' => ['required_unless:bride.lastName,', 'nullable', 'string', 'max:100'],
            'bride.lastName' => ['required_unless:bride.firstName,', 'nullable', 'string', 'max:100'],
            'groom.firstName' => ['required_unless:groom.lastName,', 'nullable', 'string', 'max:100'],
            'groom.lastName' => ['required_unless:groom.firstName,', 'nullable', 'string', 'max:100'],
            'departure' => ['required', 'string', 'in:USA,Canada,Other'],
            'spanish' => ['nullable', 'boolean'],
            'phone' => ['required', 'string', 'min:7', 'max:20'],
            'email' => ['required', 'email'],
            'destinations' => ['nullable', 'string'],
            'weddingDate' => ['required', 'string', 'min:8'],
            'agent' => ['required', 'string', 'max:100'],
            'source' => ['nullable', 'string', 'max:100'],
            'message' => ['nullable', 'string']
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
            'bride.firstName' => 'first name',
            'bride.lastName' => 'last name',
            'groom.firstName' => 'first name',
            'groom.lastName' => 'last name',
            'departure' => 'group departure',
            'phone' => 'phone number',
            'agent' => 'specialist'
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
            'bride.firstName.required_unless' => 'The first name field is required.',
            'bride.lastName.required_unless' => 'The last name field is required.',
            'groom.firstName.required_unless' => 'The first name field is required.',
            'bride.lastName.required_unless' => 'The last name field is required.',
        ];
    }
}
