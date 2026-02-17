<?php

namespace App\Http\Controllers\Bookings;

use App\Events\BookingSubmitted;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bookings\AddClient;
use App\Http\Requests\Bookings\NewBooking;
use App\Models\Booking;
use App\Models\Client;
use App\Models\Guest;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class BookingController extends Controller
{
    public function addClient(AddClient $request)
    {
        return response()->json();
    }

    public function newBooking($step, NewBooking $request)
    {
        if($step == 2){
            $duplicatesInRequest = collect();
            $guests = collect($request->validated()['guests']);

            foreach ($guests as $guest) {
                $duplicatesInRequestIndex = $guests->filter(function($g) use ($guest) {        
                    return $g['firstName'] === $guest['firstName']
                        && $g['lastName'] === $guest['lastName']
                        && (Carbon::parse($g['birthDate'], 'UTC')->format('Y-m-d')) === Carbon::parse($guest['birthDate'], 'UTC')->format('Y-m-d');
                })->keys();

                if ($duplicatesInRequestIndex->count() > 1) {
                    $duplicatesInRequest = $duplicatesInRequest->merge($duplicatesInRequestIndex);
                }
            }
            
            if ($duplicatesInRequest->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'duplicate_guests_in_request' => $duplicatesInRequest->toArray(),
                ]);
            }
        }  
    
        if ($step < 3) {
            return response()->json();
        }

        $booking = Booking::create([
            'hotel_assistance' => $request->input('hotelAssistance'),
            'hotel_preferences' => $request->input('hotelAssistance') ? $request->input('hotelPreferences') : null,
            'hotel_name' => $request->input('hotelAssistance') ? null : $request->input('hotelName'),
            'room_category' => $request->input('roomCategory'),
            'room_category_name' => $request->input('roomCategory') ? $request->input('roomCategoryName') : null,
            'check_in' => $request->input('checkIn'),
            'check_out' => $request->input('checkOut'),
            'special_requests' => $request->input('specialRequests'),
            'budget' => $request->input('budget'),
            'transportation' => $request->input('transportation'),
            'departure_gateway' => $request->input('transportation') ? $request->input('departureGateway') : null,
            'flight_preferences' => $request->input('transportation') ? $request->input('flightPreferences') : null,
            'airline_membership_number' => $request->input('transportation') ? $request->input('airlineMembershipNumber') : null,
            'known_traveler_number' => $request->input('transportation') ? $request->input('knownTravelerNumber') : null,
            'flight_message' => $request->input('transportation') ? $request->input('flightMessage') : null,
            'email' => $request->input('clients.0.email'),
            'reservation_leader_first_name' => $request->input('clients.0.firstName'),
            'reservation_leader_last_name' => $request->input('clients.0.lastName'),
        ]);

        $client = Client::firstOrCreate(
            ['email' => $request->input('clients.0.email')],
            [
                'first_name' => $request->input('clients.0.firstName'),
                'last_name' => $request->input('clients.0.lastName')
            ]
        );

        $bookingClient = $booking->clients()->create([
            'client_id' => $client->id,
            'first_name' => $request->input('clients.0.firstName'),
            'last_name' => $request->input('clients.0.lastName'),
            'card_id' => null,
            'telephone' => $request->input('clients.0.phone'),
            'insurance' => $request->input('insurance'),
            'insurance_signed_at' => now()
        ]);

        if ($request->input('hasSeperateClients')) {
            $seperateClients = array_filter(array_slice($request->input('clients'), 1), function ($client) use ($request) {
                return in_array($client['email'], $request->input('clients.*.email'));
            });

            foreach ($seperateClients as $seperateClient) {
                $booking->clients()->create([
                    'client_id' => Client::firstOrCreate(
                        ['email' => $seperateClient['email']],
                        [
                            'first_name' => $seperateClient['firstName'],
                            'last_name' => $seperateClient['lastName']
                        ]
                    )->id,
                    'first_name' => $seperateClient['firstName'],
                    'last_name' => $seperateClient['lastName'],
                    'telephone' => $seperateClient['phone'],
                ]);
            }
        }

        foreach ($request->input('guests') as $guest) {    
            Guest::create([
                'booking_client_id' => $request->input('hasSeperateClients') ? $booking->clients->firstWhere('client.email', $guest['client'])->id : $bookingClient->id,
                'first_name' => $guest['firstName'],
                'last_name' => $guest['lastName'],
                'gender' => $guest['gender'],
                'birth_date' => $guest['birthDate'],
                'check_in' => $request->input('checkIn'),
                'check_out' => $request->input('checkOut'),
                'insurance' => $request->input('hasSeperateClients') ? ($guest['client'] == $bookingClient->client->email ? $request->input('insurance') : null) : $request->input('insurance'),
                'transportation' => $request->input('transportation'),
                'transportation_type' => $request->input('transportation') ? 1 : null,
                'custom_group_airport' => null,
            ]);
        }

        event(new BookingSubmitted($booking));

        return response()->json();
    }
}
