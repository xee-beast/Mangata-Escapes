<?php

namespace App\Http\Requests\Couples;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Booking;

class AddReservationClient extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $booking = Booking::whereHas('clients', function ($query) {
            $query->where('reservation_code', $this->input('booking.code'))
                ->whereHas('client', function ($query) {
                    $query->where('email', $this->input('booking.email'));
                });
        })->first();

        $existingBookingClientEmails = $booking ? $booking->clients->pluck('client.email')->filter()->toArray() : [];

        $allClientsInRequest = array_merge(
            $existingBookingClientEmails,
            array_map(function ($client) {
                return $client['email'] ?? null;
            }, $this->input('clients', []))
        );

        return [
            'newClient.firstName' => ['required', 'string', 'max:50'],
            'newClient.lastName' => ['required', 'string', 'max:50'],
            'newClient.phone' => ['required', 'numeric', 'min:1', 'digits_between:7,12'],
            'newClient.email' => [
                'required',
                'email:rfc,dns',
                Rule::notIn(array_filter($allClientsInRequest))
            ],
        ];
    }

    public function attributes()
    {
        return [
            'newClient.firstName' => 'first name',
            'newClient.lastName' => 'last name',
            'newClient.phone' => 'phone number',
            'newClient.email' => 'email'
        ];
    }

    public function messages()
    {
        return [
            'newClient.email.not_in' => 'This email is already associated with this booking.'
        ];
    }
}
