<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserPasswordByAdmin extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'password' => ['required', 'regex:/^(?=.*?[a-z])(?=.*?[A-Z])(?=.*?[0-9]).+$/', 'min:8', 'max:32'],
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
            'password.regex' => 'The password must contain at least 1 lowercase letter, 1 uppercase letter & 1 number.',
        ];
    }
}
