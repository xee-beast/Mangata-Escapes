<?php

namespace App\Http\Controllers\Couples;

use App\Http\Controllers\Controller;
use App\Http\Requests\Couples\AddClient;
use App\Http\Requests\Couples\NewBooking;
use App\Http\Requests\Couples\AccommodationSearch;
use App\Models\Booking;
use App\Models\BookingRoomBlock;
use App\Models\Group;
use App\Http\Resources\ImageResource;
use App\Models\Guest;
use App\Models\RoomBlock;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\Pivot;

class BookingController extends Controller
{
    /**
     * Capture a new booking.
     *
     * @param \App\Models\Group $group
     * @param int $step
     * @param  \App\Http\Requests\Couples\NewBooking  $request
     * @return \Illuminate\Http\Response
     */
    public function newBooking(Group $group, $step, NewBooking $request)
    {
        if ($step == 2) {
            $duplicateGuests = collect();
            $duplicatesInRequest = collect();
            $guests = collect($request->validated()['guests']);

            foreach ($guests as $index => $guest) {
                $duplicatesInRequestIndex = $guests->filter(function($g) use ($guest) {
                    return $g['firstName'] === $guest['firstName']
                        && $g['lastName'] === $guest['lastName']
                        && $g['birthDate'] === $guest['birthDate'];
                })->keys();

                if ($duplicatesInRequestIndex->count() > 1) {
                    $duplicatesInRequest = $duplicatesInRequest->merge($duplicatesInRequestIndex);
                }

                $duplicateGuest = Guest::whereHas('booking_client.booking.group', function ($query) use ($group) {
                        $query->where('id', $group->id);
                    })
                    ->where('first_name', $guest['firstName'])
                    ->where('last_name', $guest['lastName'])
                    ->where('birth_date', Carbon::parse($guest['birthDate'], 'UTC')->format('Y-m-d'))
                    ->first();

                if ($duplicateGuest) {
                    $duplicateGuests->push($index);
                }
            }

            if ($duplicateGuests->isNotEmpty() || $duplicatesInRequest->isNotEmpty()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'duplicate_guests_in_request' => $duplicatesInRequest->toArray(),
                    'duplicate_guests' => $duplicateGuests->toArray(),
                ]);
            }
        }

        if ($step < 3) {
            return response()->json();
        }

        if (!$group->is_fit) {
            $bookingPreview = $this->getBookingPreview($request);
            $minimumPayment = $bookingPreview->getMinimumPayment($bookingPreview->clients->first());

            if ($step == 3) {
                $transportationRate = 0;

                if($group->transportation) {
                    $transportationRate = $bookingPreview->guests->count() == 1
                        ? $group->defaultAirport()->single_transportation_rate
                        : $group->defaultAirport()->transportation_rate;
                }

                return response()->json([
                    'subTotal' => $bookingPreview->getClientTotal($bookingPreview->clients->first()),
                    'transportationRate' => $transportationRate * $bookingPreview->clients->first()->guests->count(),
                    'insuranceRate' => $bookingPreview->getInsuranceRates($bookingPreview->clients->first()->guests, true)->reduce(function ($insuranceTotal, $insurance) {
                        return $insuranceTotal + ($insurance->rate * $insurance->quantity);
                    }, 0),
                    'guests' => $bookingPreview->guests->count(),
                    'minimumDeposit' => ceil($minimumPayment * 100) / 100,
                    'bookingDeposit' => ceil($bookingPreview->minimum_deposit * 100) / 100,
                ]);
            }

            if ($request->input('deposit') < $minimumPayment) {
                throw \Illuminate\Validation\ValidationException::withMessages(['deposit' => 'The minimum payment amount for this booking is $' . ceil($minimumPayment * 100) / 100 . '.']);
            }
        }

        $booking = $group->bookings()->create([
            'special_requests' => $request->input('specialRequests'),
        ]);

        BookingRoomBlock::create([
            'booking_id' => $booking->id,
            'room_block_id' => $request->input('room'),
            'bed' => $request->input('bed'),
            'check_in' => $request->input('checkIn'),
            'check_out' => $request->input('checkOut'),
        ]);

        $client = \App\Models\Client::firstOrCreate(
            ['email' => $request->input('clients.0.email')],
            [
                'first_name' => $request->input('clients.0.firstName'),
                'last_name' => $request->input('clients.0.lastName')
            ]
        );

        if (!$group->is_fit) {
            $clientAddress = $client->addresses()->create([
                'country_id' => $request->input('address.country') ? $request->input('address.country') : null,
                'other_country' => $request->input('address.country') ? null : $request->input('address.otherCountry'),
                'state_id' => $request->input('address.country') ? $request->input('address.state') : null,
                'other_state' => $request->input('address.country') ? null : $request->input('address.otherState'),
                'city' => $request->input('address.city'),
                'line_1' => $request->input('address.line1'),
                'line_2' => $request->input('address.line2'),
                'zip_code' => $request->input('address.zipCode')
            ]);

            $clientCard = $client->cards()->create([
                'name' => $request->input('card.name'),
                'type' => $request->input('card.type'),
                'number' => $request->input('card.number'),
                'expiration_date' => $request->input('card.expMonth') . $request->input('card.expYear'),
                'code' => $request->input('card.code'),
                'address_id' => $clientAddress->id
            ]);
        }

        $bookingClient = $booking->clients()->create([
            'client_id' => $client->id,
            'first_name' => $request->input('clients.0.firstName'),
            'last_name' => $request->input('clients.0.lastName'),
            'card_id' => !$group->is_fit ? $clientCard->id : null,
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
                    'client_id' => \App\Models\Client::firstOrCreate(
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
            \App\Models\Guest::create([
                'booking_client_id' => $request->input('hasSeperateClients') ? $booking->clients->firstWhere('client.email', $guest['client'])->id : $bookingClient->id,
                'first_name' => $guest['firstName'],
                'last_name' => $guest['lastName'],
                'gender' => $guest['gender'],
                'birth_date' => $guest['birthDate'],
                'check_in' => $request->input('checkIn'),
                'check_out' => $request->input('checkOut'),
                'insurance' => $request->input('hasSeperateClients') ? ($guest['client'] == $bookingClient->client->email ? $request->input('insurance') : null) : $request->input('insurance'),
                'transportation' => $group->transportation ? $request->input('transportation') : false,
                'transportation_type' => ($group->transportation && $request->input('transportation')) ? 1 : null,
                'custom_group_airport' => ($group->transportation && $request->input('transportation')) ? $group->defaultAirport()->id : null,
            ]);
        }

        if (!$group->is_fit) {
            $bookingClient->payments()->create([
                'card_id' => $clientCard->id,
                'amount' => $request->input('deposit')
            ]);
        }

        event(new \App\Events\BookingSubmitted($booking));

        return response()->json();
    }

    /**
     * Validate seperate client information.
     *
     * @param \App\Models\Group $group
     * @param  \App\Http\Requests\Couples\AddClient  $request
     * @return \Illuminate\Http\Response
     */
    public function addClient(Group $group, AddClient $request)
    {
        return response()->json();
    }

    /**
     * Generate a booking model with all it's relations without saving to database.
     *
     * @param \App\Http\Requests\Couples\NewBooking  $request
     * @return \App\Models\Booking
     */
    protected function getBookingPreview($request)
    {
        $booking = Booking::make([
            'group_id' => $request->route('group')->id,
        ]);

        $booking->room_block_id = $request->input('room');
        $booking->check_in = $request->input('checkIn');
        $booking->check_out = $request->input('checkOut');

        $room_block = RoomBlock::where('id', $request->input('room'))->first();
        $booking->setRelation('roomBlocks', new \Illuminate\Database\Eloquent\Collection([$room_block]));

        $room_block->pivot = new Pivot([
            'bed' => $request->input('bed'),
            'check_in' => $request->input('checkIn'),
            'check_out' => $request->input('checkOut'),
        ], $booking);

        $bookingClients = [];
        $bookingGuests = [];
        $guestCount = 0;
        foreach($request->input('clients') as $clientIndex => $client) {
            $bookingClient = \App\Models\BookingClient::make([
                'first_name' => $client['firstName'],
                'last_name' => $client['lastName']
            ]);

            $bookingClientGuests = [];
            foreach(($request->input('hasSeperateClients') ? array_filter($request->input('guests'), function ($guest) use ($client) { return $guest['client'] == $client['email']; }) : $request->input('guests')) as $guest) {
                $guestCount++;

                $bookingClientGuest = \App\Models\Guest::make([
                    'birth_date' => $guest['birthDate'],
                    'check_in' => $request->input('checkIn'),
                    'check_out' => $request->input('checkOut'),
                    'insurance' => $clientIndex > 0 ? null : $request->input('insurance')
                ]);
                $bookingClientGuest->id = $guestCount; // Dirty & Hacky

                array_push($bookingClientGuests, $bookingClientGuest);
                array_push($bookingGuests, $bookingClientGuest);
            }

            $bookingClient->setRelation('guests', new \Illuminate\Database\Eloquent\Collection($bookingClientGuests));

            array_push($bookingClients, $bookingClient);
        }

        $booking->setRelation('clients', new \Illuminate\Database\Eloquent\Collection($bookingClients));
        $booking->setRelation('guests', new \Illuminate\Database\Eloquent\Collection($bookingGuests));


        return $booking;
    }

    public function search(Group $group, AccommodationSearch $request) {
        $group->load(['hotels.hotel.rooms.room_blocks.child_rates']);
        $filters = $request->all();

        $allRooms = collect();

        foreach ($group->hotels as $hotel_block) {
            foreach ($hotel_block->rooms as $room_block) {
                if (Booking::isRoomBlockAvailable($room_block, $filters) && $room_block->is_active && $room_block->is_visible) {
                    $filters_checkIn = Carbon::parse($filters['checkIn']);
                    $filters_checkOut = Carbon::parse($filters['checkOut']);

                    $room = [
                        'hotelId' => $hotel_block->id,
                        'hotelName' => $hotel_block->hotel->name,
                        'hotelDescription' => $hotel_block->hotel->description,
                        'hotelImages' => ImageResource::collection($hotel_block->hotel->images),
                        'roomId' => $room_block->id,
                        'image' => $room_block->room->image ? new ImageResource($room_block->room->image) : null,
                        'name' => $room_block->room->name,
                        'size' => $room_block->room->size,
                        'view' => $room_block->room->view,
                        'beds' => $room_block->room->beds,
                        'maxOccupants' => $room_block->room->max_occupants,
                        'maxAdults' => $room_block->room->max_adults,
                        'formattedMaxOccupancy' => $room_block->room->formatted_max_occupancy,
                        'description' => $room_block->room->description,
                        'dates' => sprintf("%s - %s", $filters_checkIn->format('F d'), $filters_checkOut->format('F d, Y')),
                        'soldOut' => $room_block->sold_out,
                        'subTotal' => null,
                        'details' => [],
                    ];

                    if (!$room_block->sold_out && !$group->is_fit) {
                        $rates = [];

                        foreach ($room_block->rates as $rate) {
                            $rates[] = [
                                'rate' => $rate->rate,
                                'occupancy' => $rate->occupancy,
                            ];
                        }

                        if (!empty($rates)) $room['details']['rates'] = $rates;

                        $childRates = [];

                        foreach ($room_block->child_rates as $rate) {
                            $childRates[] = [
                                'rate' => $rate->rate,
                                'from' => $rate->from,
                                'to' => $rate->to,
                            ];
                        }

                        if (!empty($childRates)) $room['details']['childRates'] = $childRates;

                        $room['subTotal'] = Booking::createBookingPreview($group, $room_block, $filters)->subTotal;
                    }

                    $allRooms->push($room);
                }
            }
        }

        $sortedRooms = $allRooms->sortBy(function ($room) {
            return $room['soldOut'] ? PHP_INT_MAX : $room['subTotal'];
        })->values();

        $sortedHotels = collect();
        $lastHotelId = null;
        $currentHotel = null;

        foreach ($sortedRooms as $room) {
            if ($room['hotelId'] !== $lastHotelId) {
                if ($currentHotel) {
                    $sortedHotels->push($currentHotel);
                }

                $currentHotel = [
                    'id' => $room['hotelId'],
                    'name' => $room['hotelName'],
                    'description' => $room['hotelDescription'],
                    'images' => $room['hotelImages'],
                    'rooms' => [],
                ];
            }

            $currentHotel['rooms'][] = $room;
            $lastHotelId = $room['hotelId'];
        }

        if ($currentHotel) {
            $sortedHotels->push($currentHotel);
        }

        return $sortedHotels->values();
    }
}
