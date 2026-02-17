<?php

namespace App\Http\Controllers\Couples;

use App\Http\Controllers\Controller;
use App\Jobs\SendPaymentReminderToFitClient;
use App\Models\Booking;
use App\Models\BookingClient;
use App\Models\Client;
use App\Models\Group;
use App\Models\Guest;
use App\Notifications\FitQuoteAccepted;
use App\Notifications\FlightManifestRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PDF;

class FitQuoteController extends Controller
{
    public function acceptFitQuote(Group $group, $step, Request $request)
    {
        $client = Client::where('email', $request->booking['email'])->first();

        $request->validate([
            'booking.email' => ['required', 'email', 'bail', function ($attribute, $value, $fail) use ($client) { if (is_null($client)) { $fail('This email does not exist in our records.'); } }],
            'booking.code' => ['required', 'alpha_num', 'size:6', 'bail', function ($attributes, $value, $fail) use ($client, $group) { if (!is_null($client) && !$client->bookings()->whereHas('booking', function ($query) use ($group) { $query->where('group_id', $group->id); })->where('reservation_code', $value)->exists()) { $fail('The booking reservation code is not valid.'); } }]
        ]);

        $bookingClient = BookingClient::whereRelation('client', 'email', $request->input('booking.email'))
            ->where('reservation_code', $request->input('booking.code'))
            ->first();
        
        if ($bookingClient->booking->group->is_fit && $bookingClient->acceptedFitQuote) {
            return response()->json([
                'error' => 'accepted_fit_quote'
            ], 403);
        } elseif ($bookingClient->booking->group->is_fit && is_null($bookingClient->pendingFitQuote)) {
            return response()->json([
                'error' => 'missing_fit_quote'
            ], 403);
        }

        if ($step == 2) {
            $bookingClient->pendingFitQuote()->update([
                'accepted_at' => now(),
            ]);

            $bookingClient->client->notify(new FitQuoteAccepted($bookingClient));
            SendPaymentReminderToFitClient::dispatch($bookingClient)->delay(now()->addMinutes(15));

            if (
                $bookingClient->booking->transportedGuests->count() > 0 &&
                $bookingClient->booking->group->transportation &&
                (!is_null($bookingClient->booking->group->transportation_submit_before)) &&
                $bookingClient->booking->group->transportation_submit_before->between(Carbon::now(), Carbon::now()->addMonth())
            ) {
                $guestsWithoutManifests =  Guest::where('transportation', true)->where('booking_client_id', $bookingClient->id)->whereDoesntHave('flight_manifest')->get();
                $bookingClient->client->notify(new FlightManifestRequest($bookingClient, $guestsWithoutManifests));
            }       
        }

        return response()->json()->setStatusCode(204);
    }

    public function streamQuoteInvoice(Group $group, Request $request)
    {
        $booking = Booking::whereHas('clients', function ($query) use ($request) {
            $query->where('reservation_code', $request->input('booking.code'))->whereHas('client', function ($query) use ($request) {
                $query->where('email', $request->input('booking.email'));
            });
        })->first();

        $invoice = PDF::loadView('pdf.invoice', ['invoice' => $booking->invoice])->stream('R' . $booking->order . ' BB Quotation Invoice - ' . $group->name . '.pdf');
        $invoice->headers->set('X-Vapor-Base64-Encode', 'True');

        return $invoice;
    }
}
