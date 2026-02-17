<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncLeadOptions extends FormRequest
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
            'referralSourceOptions' => 'required|array|min:1',
            'referralSourceOptions.*.option' => 'required|string|max:255',
            'contactedUsOptions' => 'required|array|min:1',
            'contactedUsOptions.*.option' => 'required|string|max:255',
        ];
    }

    public function attributes()
    {
        return [
            'referralSourceOptions' => 'heard about us options',
            'contactedUsOptions' => 'contacted us options',
            'referralSourceOptions.*.option' => 'heard about us option',
            'contactedUsOptions.*.option' => 'contacted us option',
        ];
    }
}
