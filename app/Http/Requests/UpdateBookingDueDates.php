<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookingDueDates extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'balanceDueDate' => 'required|date|before:' . $this->individual_booking->check_in->format('m/d/Y'),
            'cancellationDate' => 'required|date|before:' . $this->individual_booking->check_in->format('m/d/Y'),
            'dueDates' => 'nullable|array',
            'dueDates.*' => 'required_with:dueDates|array',
            'dueDates.*.date' => 'required_with:dueDates|date|before:' . $this->individual_booking->check_in->format('m/d/Y'),
            'dueDates.*.type' => 'required_with:dueDates|in:price,percentage',
            'dueDates.*.amount' => 'required_with:dueDates|numeric|min:0',
        ];
    }

    public function attributes()
    {
        return [
            'dueDates.*.date' => 'due date',
            'dueDates.*.type' => 'type',
            'dueDates.*.amount' => 'amount',
        ];
    }
}
