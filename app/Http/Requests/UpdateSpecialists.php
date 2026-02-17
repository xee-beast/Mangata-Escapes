<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSpecialists extends FormRequest
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
            'specialists' => ['nullable', 'array'],
            'specialists.*.name' => ['required', 'string', 'max:255'],
            'specialists.*.email' => ['required', 'email', 'max:255'],
        ];
    }

    public function attributes()
    {
        return [
            'specialists.*.name' => 'name',
            'specialists.*.email' => 'email',
        ];
    }
}
