<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAccomodation extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'inventory' => 'nullable|integer|min:0',
            'soldOut' => 'required|boolean',
            'isVisible' => 'sometimes|boolean',
            'dates.start' => 'required|date|before:dates.end|before:' . $this->group->event_date,
            'dates.end' => 'required|date|after:dates.start|after:' . $this->group->event_date,
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
            'dates.start' => 'block dates',
            'dates.end' => 'block dates',
        ];
    }
}
