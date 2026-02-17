<?php

namespace App\Http\Controllers\Bookings;

use App\Events\FlightManifestSubmitted;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bookings\NewFlightManifest;
use App\Http\Resources\GuestResource;
use App\Models\Airport;
use App\Models\BookingClient;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class FlightManifestController extends Controller
{
    public function newFlightManifest($step, NewFlightManifest $request)
    {
        $bookingClient = BookingClient::with('guests.flight_manifest', 'booking.destination.airports')
            ->whereRelation('client', 'email', $request->input('booking.email'))
            ->where('reservation_code', $request->input('booking.code'))
            ->first();

        if (Carbon::parse($bookingClient->booking->check_in)->between(Carbon::today(), Carbon::today()->addDays(7))) {
            return response()->json([
                'error' => 'date_check',
            ], 403);
        }

        if (is_null($bookingClient->acceptedFitQuote)) {
            return response()->json([
                'error' => 'quote_check',
            ], 403);
        }

        if ($step < 2) {
            $guestsWithoutManifest = $bookingClient->guests->filter(function ($guest) {
                return empty($guest->flight_manifest) && $guest->transportation;
            });

            if ($guestsWithoutManifest->isEmpty() && $bookingClient->booking->transportation && $bookingClient->transportedGuests->count() > 0) {
                return response()->json(['success' => false], 500);
            }

            return response()->json([
                'booking' => $bookingClient->booking,
                'airports' => $bookingClient->booking->destination ? $bookingClient->booking->destination->airports : Airport::orderBy('airport_code', 'asc')->get(),
                'guests' => GuestResource::collection($guestsWithoutManifest),
                'transportation' => $bookingClient->booking->transportation && $bookingClient->transportedGuests->count() > 0,
                'clientPhone' => $bookingClient->telephone,
            ]);
        }

        $form = $request->form;
        $arrival_departure_airport_iata = null;
        $arrival_departure_airport_timezone = null;
        $arrival_departure_date = null;
        $arrival_airport = null;
        $arrival_datetime = null;
        $departure_airport = null;
        $departure_date = null;
        $departure_datetime = null;

        if ($form['arrivalDetailsRequired']) {
            $response = Http::withHeaders([
                'x-apikey' => config('services.aeroapi.api_key'),
            ])->get(config('services.aeroapi.api_url') . "/airports/{$form['arrivalDepartureAirport']}");

            $arrival_departure_airport = $response->json();

            if (isset($arrival_departure_airport['status']) && $arrival_departure_airport['status'] === 429) {
                return response()->json([
                    'errors' => ['form.arrivalDepartureAirport' => ['Something went wrong. Please try submitting the form again.']]
                ], 422);
            }
            
            if (!isset($arrival_departure_airport['code_iata']) || (isset($arrival_departure_airport['code_iata']) && $arrival_departure_airport['code_iata'] !== strtoupper($form['arrivalDepartureAirport']))) {
                return response()->json([
                    'errors' => ['form.arrivalDepartureAirport' => ['The airport code is not valid.']]
                ], 422);
            } else {
                $arrival_departure_airport_iata = $arrival_departure_airport['code_iata'];
                $arrival_departure_airport_timezone = $arrival_departure_airport['timezone'];
                $arrival_departure_date = $form['arrivalDepartureDate'];
                $arrival_airport = Airport::find($form['arrivalAirport']);
                $arrival_datetime = Carbon::parse($form['arrivalDateTime'], $arrival_airport ? $arrival_airport->timezone : 'UTC')->setTimezone('UTC')->format('Y-m-d H:i');
            }
        }

        if ($form['departureDetailsRequired']) {
            $departure_airport = Airport::find($form['departureAirport']);
            $departure_date = $form['departureDate'];
            $departure_datetime = Carbon::parse($form['departureDateTime'], $departure_airport ? $departure_airport->timezone : 'UTC')->setTimezone('UTC')->format('Y-m-d H:i');
        }

        $guests = collect($request->input('guests'));

        $bookingClient->guests->each(function ($guest) use ($guests, $request, $arrival_departure_airport_iata, $arrival_departure_airport_timezone, $arrival_departure_date, $arrival_airport, $arrival_datetime, $departure_airport, $departure_date, $departure_datetime) {
            if (! is_null($guest->flight_manifest) || is_null($flightManifest = $guests->firstWhere('id', $guest->id))) {
                return;
            }

            $arrival_date_mismatch_reason = null;
            $departure_date_mismatch_reason = null;

            if ($request->dates_mismatch && !is_null($request->dates_mismatch) && isset($request->dates_mismatch[$guest->id])) {
                if (isset($request->dates_mismatch[$guest->id]['arrival'])) {
                    $arrival_date_mismatch_reason = $request->dates_mismatch[$guest->id]['arrival']['selected_option'];
                }

                if (isset($request->dates_mismatch[$guest->id]['departure'])) {
                    $departure_date_mismatch_reason = $request->dates_mismatch[$guest->id]['departure']['selected_option'];
                }
            }

            $guest->flight_manifest()->create([
                'phone_number' => $flightManifest['phoneNumber'],
                'arrival_departure_airport_iata' => isset($flightManifest['arrivalDepartureAirport']) ? $arrival_departure_airport_iata : null,
                'arrival_departure_airport_timezone' => isset($flightManifest['arrivalDepartureAirport']) ? $arrival_departure_airport_timezone : null,
                'arrival_departure_date' => isset($flightManifest['arrivalDepartureDate']) ? $arrival_departure_date : null,
                'arrival_datetime' => isset($flightManifest['arrivalDateTime']) ? $arrival_datetime : null,
                'arrival_airport_id' => isset($flightManifest['arrivalAirport']) ? ($arrival_airport ? $arrival_airport->id : null) : null,
                'arrival_airline' => isset($flightManifest['arrivalAirline']) ? $flightManifest['arrivalAirline'] : null,
                'arrival_number' => isset($flightManifest['arrivalNumber']) ? $flightManifest['arrivalNumber'] : null,
                'arrival_manual' => isset($flightManifest['arrivalManual']) ? $flightManifest['arrivalManual'] : 0,
                'arrival_date_mismatch_reason' => $arrival_date_mismatch_reason,
                'departure_date' => isset($flightManifest['departureDate']) ? $departure_date : null,
                'departure_datetime' => isset($flightManifest['departureDateTime']) ? $departure_datetime : null,
                'departure_airport_id' => isset($flightManifest['departureAirport']) ? ($departure_airport ? $departure_airport->id : null) : null,
                'departure_airline' => isset($flightManifest['departureAirline']) ? $flightManifest['departureAirline'] : null,
                'departure_number' => isset($flightManifest['departureNumber']) ? $flightManifest['departureNumber'] : null,
                'departure_manual' => isset($flightManifest['departureManual']) ? $flightManifest['departureManual'] : 0,
                'departure_date_mismatch_reason' => $departure_date_mismatch_reason,
            ]);
        });

        event(new FlightManifestSubmitted($bookingClient, $request->input('dates_mismatch')));

        return response()->json()->setStatusCode(201);
    }
}
