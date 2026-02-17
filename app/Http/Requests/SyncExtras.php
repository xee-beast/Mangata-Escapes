<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncExtras extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [];

        if (!$this->group || ($this->group && $this->group->is_fit)) {
            $rules = array_merge($rules, [
                'fitRate.accommodation' => ['required', 'numeric'],
                'fitRate.insurance' => ['required', 'numeric'],
            ]);
        }

        $rules = array_merge($rules, [
            'extras.*.description' => ['required', 'string', 'max:200'],
            'extras.*.price' => ['required', 'numeric'],
            'extras.*.quantity' => ['required', 'integer', 'min:1']
        ]);

        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'fitRate.accommodation' => 'accommodation charges',
            'fitRate.insurance' => 'insurance charges',
            'extras.*.description' => 'description',
            'extras.*.price' => 'price',
            'extras.*.quantity' => 'quantity'
        ];
    }
}
