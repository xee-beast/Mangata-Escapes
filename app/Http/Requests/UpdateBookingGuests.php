<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookingGuests extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('update', $this->booking);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'guests' => 'required|array|min:1',
            'guests.*.id' => 'nullable|integer|min:1',
            'guests.*.firstName' => 'required|string|max:50',
            'guests.*.lastName' => 'required|string|max:50',
            'guests.*.gender' => ['required', Rule::in(['M', 'F'])],
            'guests.*.dates.start' => 'required|date|before:guests.*.dates.end',
            'guests.*.dates.end' => 'required|date|after:guests.*.dates.start',
            'insurance' => 'nullable|boolean',
            'guests.*.client' => ['required', Rule::exists('booking_clients', 'id')->where('booking_id', $this->booking->id)],
            'guests.*.birthDate' => ['required', 'date', 'before:' . $this->group->event_date->format('Y-m-d')],
        ];

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
            'guests.*.firstName' => 'first name',
            'guests.*.lastName' => 'last name',
            'guests.*.gender' => 'gender',
            'guests.*.birthDate' => 'date of birth',
            'guests.*.dates.start' => 'check in date',
            'guests.*.dates.end' => 'check out date',
            'guests.*.client' => 'client',
        ];
    }
}
