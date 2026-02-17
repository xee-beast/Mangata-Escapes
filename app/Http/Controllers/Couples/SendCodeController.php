<?php

namespace App\Http\Controllers\Couples;

use App\Http\Controllers\Controller;
use App\Http\Requests\Couples\SendCode;
use App\Models\Client;
use App\Models\Group;
use App\Notifications\BookingReservationCodeNotification;

class SendCodeController extends Controller
{
    /**
     * Send the booking reservation code to the requested client.
     *
     * @param \App\Models\Group $group
     * @param \App\Http\Requests\Couples\SendCode $request
     * @return \Illuminate\Http\Response
     */
    public function sendCode(Group $group, SendCode $request) 
    {
        $client = Client::where('email', $request->input('email'))->first();

        if (is_null($client)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['email' => 'This email is not related to any existing booking.']);
        }

        $bookingClients = $client->bookings()->whereHas(
            'booking.group', function ($query) use ($group) {
                $query->where('id', $group->id);
            })->get();

        if ($bookingClients->isEmpty()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['email' => 'There is no booking registered with the provided email for this wedding group.']);
        }

        $client->notify(new BookingReservationCodeNotification($bookingClients));
    }
}
