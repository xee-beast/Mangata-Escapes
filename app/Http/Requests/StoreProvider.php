<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProvider extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' =>  'required|string|max:100|unique:providers,name',
            'abbreviation' => 'required|string|max:50|unique:providers,abbreviation',
            'phoneNumber' => 'required|string|max:20|unique:providers,phone_number',
            'email' => 'required|email|max:255|unique:providers,email',
        ];
    }
}
