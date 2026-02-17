<?php

namespace App\Http\Requests\Couples;

use Illuminate\Foundation\Http\FormRequest;

class ViewReservation extends FormRequest
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
        $client = \App\Models\Client::where('email', $this->input('booking.email'))->first();

        $validation = [
            'booking.email' => ['required', 'email', 'bail', function ($attribute, $value, $fail) use ($client) { if (is_null($client)) { $fail('This email does not exist in our records.'); } }],
            'booking.code' => ['required', 'alpha_num', 'size:6', 'bail', function ($attributes, $value, $fail) use ($client) { if (!is_null($client) && !$client->bookings()->whereHas('booking', function ($query) { $query->where('group_id', $this->route('group')->id); })->where('reservation_code', $value)->exists()) { $fail('The booking reservation code is not valid.'); } }],
            'validate' => ['nullable', 'boolean']
        ];

        return $validation;
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'booking.email' => 'email',
            'booking.code' => 'booking reservation code',
        ];
    }
}
