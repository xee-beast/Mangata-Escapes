<?php

namespace App\Http\Requests\Couples;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddClient extends FormRequest
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
            'newClient.firstName' => ['required', 'string', 'max:50'],
            'newClient.lastName' => ['required', 'string', 'max:50'],
            'newClient.phone' => ['required', 'numeric', 'min:1', 'digits_between:7,12'],
            'newClient.email' => ['required', 'email:rfc,dns', Rule::notIn(array_map(function ($client) { return $client['email']; }, $this->input('clients', [])))],
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
            'newClient.firstName' => 'first name',
            'newClient.lastName' => 'last name',
            'newClient.phone' => 'phone number',
            'newClient.email' => 'email'
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
            'newClient.email.not_in' => 'The email must be unique.'
        ];
    }
}
