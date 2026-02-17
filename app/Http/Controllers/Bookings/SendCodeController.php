<?php

namespace App\Http\Controllers\Bookings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bookings\SendCode;
use App\Models\Client;
use App\Notifications\BookingReservationCodeNotification;
use Illuminate\Validation\ValidationException;

class SendCodeController extends Controller
{
    public function sendCode(SendCode $request) 
    {
        $client = Client::where('email', $request->input('email'))->first();

        if (is_null($client)) {
            throw ValidationException::withMessages(['email' => 'This email is not related to any existing booking.']);
        }

        $bookingClients = $client->bookings()->whereHas(
            'booking', function ($query) {
                $query->whereNull('group_id');
            })->get();

        if ($bookingClients->isEmpty()) {
            throw ValidationException::withMessages(['email' => 'There is no booking registered with the provided email.']);
        }

        $client->notify(new BookingReservationCodeNotification($bookingClients));
    }
}
