<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProvider extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' =>  ['required', 'string', 'max:100', Rule::unique('providers', 'name')->ignore($this->route('provider'))],
            'abbreviation' => ['required', 'string', 'max:50', Rule::unique('providers', 'abbreviation')->ignore($this->route('provider'))],
            'phoneNumber' => ['required', 'string', 'max:20', Rule::unique('providers', 'phone_number')->ignore($this->route('provider'))],
            'email' => ['required', 'email', 'max:255', Rule::unique('providers', 'email')->ignore($this->route('provider'))],
        ];
    }
}
