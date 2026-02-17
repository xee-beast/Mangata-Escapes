<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoomArrangements extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'roomArrangements' => ['required', 'array', 'min:1'],
            'roomArrangements.*.hotel' => ['required', 'string', 'max:255'],
            'roomArrangements.*.room' => ['required', 'string', 'max:255'],
            'roomArrangements.*.bed' => ['required', 'string', 'max:255'],
            'roomArrangements.*.dates' => ['required', 'array'],
            'roomArrangements.*.dates.start' => ['required', 'date', 'before:roomArrangements.*.dates.end', 'afterOrEqual:' . $this->individual_booking->check_in->format('m/d/Y')],
            'roomArrangements.*.dates.end' => ['required', 'date', 'after:roomArrangements.*.dates.start', 'beforeOrEqual:' . $this->individual_booking->check_out->format('m/d/Y')],
        ];
    }

    public function attributes()
    {
        return [
            'roomArrangements.*.hotel' => 'hotel',
            'roomArrangements.*.room' => 'room',
            'roomArrangements.*.bed' => 'bed',
            'roomArrangements.*.dates.start' => 'booking date',
            'roomArrangements.*.dates.end' => 'booking date',
        ];
    }
}
