<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUser extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'firstName' => 'required|string|max:50',
            'lastName' => 'required|string|max:50',
            'username' => ['required', 'regex:/^[A-Za-z0-9]+(_|-|\.)?[A-Za-z0-9]+$/', 'unique:users,username', 'max:50'],
            'email' => 'required|email|unique:users,email|max:255',
        ];
    }
}
