<?php

namespace App\Http\Controllers\Couples;

use App\Events\FlightManifestSubmitted;
use App\Http\Controllers\Controller;
use App\Http\Requests\Couples\NewFlightManifest;
use App\Http\Resources\GuestResource;
use App\Models\Airport;
use App\Models\BookingClient;
use App\Models\Group;
use App\Models\Guest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class FlightManifestController extends Controller
{
    /**
     * Get guests to manifest.
     *
     * @param \App\Models\Group $group
     * @param int $step
     * @param \App\Http\Requests\Couples\NewFlightManifest $request
     * @return \Illuminate\Http\Response
     */
    public function newFlightManifest(Group $group, $step, NewFlightManifest $request)
    {
        $bookingClient = BookingClient::with('guests.flight_manifest')
            ->whereRelation('client', 'email', $request->input('booking.email'))
            ->where('reservation_code', $request->input('booking.code'))
            ->first();
        
        if ($bookingClient->booking->group->is_fit && is_null($bookingClient->acceptedFitQuote)) {
            return response()->json([
                'message' => 'A quote has not been agreed upon yet.'
            ], 403);
        }

        if ($step < 2) {
            $guestsWithoutManifest = $bookingClient->guests->filter(function ($guest) {
                return empty($guest->flight_manifest) && $guest->transportation;
            });
            
            if ($guestsWithoutManifest->isEmpty() && $bookingClient->transportedGuests->count() > 0) {
                return response()->json(['success' => false], 500);
            }

            $guestsWithCombinedDates = $guestsWithoutManifest->map(function($guest) use ($group) {
                $duplicateGuests = Guest::where('first_name', $guest->first_name)
                    ->where('last_name', $guest->last_name)
                    ->where('birth_date', $guest->birth_date->format('Y-m-d'))
                    ->whereHas('booking_client.booking', fn($q) => $q->where('group_id', $group->id))
                    ->get();

                if ($duplicateGuests->count() > 1) {
                    $minCheckIn = $duplicateGuests->min('check_in');
                    $maxCheckOut = $duplicateGuests->max('check_out');

                    $guest->check_in = $minCheckIn;
                    $guest->check_out = $maxCheckOut;
                }

                return $guest;
            });

            return response()->json([
                'guests' => GuestResource::collection($guestsWithCombinedDates),
                'transportation' => $bookingClient->transportedGuests->count() > 0,
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

    public function getFlightTime(Request $request)
    {
        $rules = [
            'type' => 'required|in:arrival,departure',
            'departureAirport' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (request('type') === 'arrival') {
                        if (strlen($value) !== 3) {
                            $fail('The departure airport must be exactly 3 characters.');
                        } elseif (!ctype_alpha($value)) {
                            $fail('The departure airport must contain only letters.');
                        }
                    }
                }
            ],
            'departureDate' => 'required|date',
            'arrivalAirport' => 'required_if:type,arrival',
            'airline' => 'required',
            'flightNumber' => 'required|numeric',
        ];

        $validator = Validator::make($request->all(), $rules);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->type === 'arrival') {
            $response = Http::withHeaders([
                'x-apikey' => config('services.aeroapi.api_key'),
            ])->get(config('services.aeroapi.api_url') . "airports/{$request->departureAirport}");
        
            $departure_airport = $response->json();
            
            if (isset($departure_airport['status']) && $departure_airport['status'] === 429) {
                return response()->json(['errors' => 'Something went wrong. Please re-enter the flight information to try again.'], 404);
            }
            
            if (!isset($departure_airport['code_iata']) || (isset($departure_airport['code_iata']) && $departure_airport['code_iata'] !== strtoupper($request->departureAirport))) {
                return response()->json(['errors' => 'The airport code is not valid.'], 404);
            } else {
                $arrival_airport = Airport::find($request->arrivalAirport);
                $arrival_airport_iata = $arrival_airport->airport_code;
                $arrival_airport_timezone = $arrival_airport->timezone;
                $departure_airport_iata = $departure_airport['code_iata'];
                $departure_airport_timezone = $departure_airport['timezone'];
            }
        } else {
            $departure_airport = Airport::find($request->departureAirport);
            $departure_airport_iata = $departure_airport->airport_code;
            $departure_airport_timezone = $departure_airport->timezone;
        }

        $departure_from = Carbon::parse($request->departureDate, $departure_airport_timezone)->startOfDay()->setTimezone('UTC')->format('Y-m-d\TH:i:s\Z');
        $departure_to = Carbon::parse($request->departureDate, $departure_airport_timezone)->endOfDay()->setTimezone('UTC')->format('Y-m-d\TH:i:s\Z');

        $response = Http::withHeaders([
                'x-apikey' => config('services.aeroapi.api_key'),
            ])->get(config('services.aeroapi.api_url') . "schedules/{$departure_from}/{$departure_to}", [
                'origin' => $departure_airport_iata,
                'include_codeshares' => 'true',
                'include_regional' => 'true',
                'airline' => $request->airline,
                'flight_number' => $request->flightNumber,
            ]);

        $formatted_response = json_decode($response, true);
        
        if (isset($formatted_response['status']) && $formatted_response['status'] === 429) {
            return response()->json(['errors' => 'Something went wrong. Please re-enter the flight information to try again or enter time manually.'], 404);
        }

        if (isset($formatted_response['status']) && $formatted_response['status'] === 400) {
            return response()->json(['errors' => 'The system cannot search the flights that are more than 1 year in the future. Enter time manually if you want to submit your flight information right now.'], 404);
        }
        
        $scheduled_flights = $formatted_response['scheduled'];
        $flights = [];

        if (count($scheduled_flights) > 0) {
            foreach ($scheduled_flights as $flight) {
                if ($request->type === 'arrival') {
                    if ($arrival_airport_iata === $flight['destination_iata'] && $request->airline . $request->flightNumber === $flight['ident_iata']) {
                        $flights[] = [
                            'utc_datetime' => Carbon::parse($flight['scheduled_in'], 'UTC')->format('Y-m-d H:i'),
                            'airport_datetime' => Carbon::parse($flight['scheduled_in'], 'UTC')->setTimezone($arrival_airport_timezone)->format('Y-m-d H:i'),
                            'airport_datetime_formatted' => Carbon::parse($flight['scheduled_in'], 'UTC')->setTimezone($arrival_airport_timezone)->format('m/d/Y H:i'),
                        ];
                    }
                } else {
                    if ($departure_airport_iata === $flight['origin_iata'] && $request->airline . $request->flightNumber === $flight['ident_iata']) {
                        $flights[] = [
                            'utc_datetime' => Carbon::parse($flight['scheduled_out'], 'UTC')->format('Y-m-d H:i'),
                            'airport_datetime' => Carbon::parse($flight['scheduled_out'], 'UTC')->setTimezone($departure_airport_timezone)->format('Y-m-d H:i'),
                            'airport_datetime_formatted' => Carbon::parse($flight['scheduled_out'], 'UTC')->setTimezone($departure_airport_timezone)->format('m/d/Y H:i'),
                        ];
                    }
                }
            }

            return response()->json(['flights' => $flights]);
        } else {
            return response()->json(['errors' => 'No flights found. Make sure flight details are correct or enter time manually.'], 404);
        }
    }
}
