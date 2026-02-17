<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBooking;
use App\Http\Requests\UpdateBooking;
use App\Http\Requests\UpdateBookingFlightManifests;
use App\Http\Requests\UpdateBookingGuests;
use App\Http\Resources\BookingResource;
use App\Http\Resources\CountryResource;
use App\Http\Resources\GroupResource;
use App\Http\Resources\GuestResource;
use App\Models\Airline;
use App\Models\Airport;
use App\Models\Booking;
use App\Models\BookingRoomBlock;
use App\Models\Client;
use App\Models\Country;
use App\Models\FlightManifest;
use App\Models\Group;
use App\Models\Guest;
use App\Models\RoomBlock;
use App\Models\TransportationType;
use App\Notifications\BookingCancellation;
use App\Services\BookingService;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use PDF;

class BookingController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Booking::class, 'booking');
    }

    /**
     * Display a listing of the resource.
     *
     * @param \App\Models\Group $group
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Group $group, Request $request)
    {
        $bookings = $group
            ->bookings()
            ->withTrashed()
            ->ordered()
            ->with([
                'clients.guests',
                'clients.card',
                'clients.payments',
                'clients.pendingFitQuote',
                'clients.acceptedFitQuote',
                'clients.discardedFitQuote',
                'roomBlocks.hotel_block',
                'roomBlocks.rates',
                'roomBlocks.child_rates',
                'trackedChanges',
                'paymentArrangements',
            ]);

        if ($request->has('room_category') && !empty($request->room_category)) {
            $roomCategoryIds = is_array($request->room_category)
                ? $request->room_category
                : explode(',', $request->room_category);

            $bookings->whereHas('roomBlocks.room', function ($query) use ($roomCategoryIds) {
                $query->whereIn('id', $roomCategoryIds);
            });
        }

        $bookings = $bookings->get();

        $totalAdultPax = $bookings->reduce(function ($carry, $booking) {
            return $carry + $booking->getTotalAdultPax();
        }, 0);

        $roomCategories = $group->bookings()
            ->withTrashed()
            ->with('roomBlocks.room')
            ->get()
            ->pluck('roomBlocks')
            ->collapse()
            ->pluck('room')
            ->unique('id')
            ->map(function($room) {
                return [
                    'id' => $room->id,
                    'name' => $room->name
                ];
            })
            ->values();

        return BookingResource::collection($bookings)->additional([
            'group' => new GroupResource($group->load(['hotels.rooms', 'provider', 'travel_agent', 'destination', 'due_dates', 'airports.airport', 'airports.transfer'])),
            'countries' => CountryResource::collection(Country::with('states')->whereHas('states')->orderBy('name')->get()),
            'can' => [
                'create' => $request->user()->can('create', Booking::class),
            ],
            'total_adult_pax' => $totalAdultPax,
            'room_categories' => $roomCategories,
        ]);
    }

    public function updateNotes(Request $request, Group $group, Booking $booking, BookingService $bookingService)
    {
        $bookingService->updateNotes($request, $booking);

        return response()->json(['notes' => $booking->notes], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \App\Models\Group $group
     * @param  \App\Http\Requests\StoreBooking  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Group $group, StoreBooking $request)
    {
        $warnings = [];
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

            $duplicateGuests = Guest::whereHas('booking_client.booking', function ($query) use ($group) {
                    $query->where('group_id', $group->id);
                })
                ->where('first_name', $guest['firstName'])
                ->where('last_name', $guest['lastName'])
                ->where('birth_date', Carbon::parse($guest['birthDate'], 'UTC')->format('Y-m-d'))
                ->get();

            if ($duplicateGuests->isNotEmpty()) {
                foreach($duplicateGuests as $duplicateGuest){
                    $warning = ucwords($duplicateGuest->first_name) . ' ' . ucwords($duplicateGuest->last_name) . ' is already booked in booking #'. $duplicateGuest->booking_client->booking->order;
                    if (!in_array($warning, $warnings)) {
                        $warnings[] = $warning;
                    }
                }
            }
        }

        if ($duplicatesInRequest->isNotEmpty()) {
            throw ValidationException::withMessages([
                'duplicate_guests_in_request' => $duplicatesInRequest->toArray(),
            ]);
        }

        if (!$request->ignoreGuestWarnings && count($warnings) > 0) {
            return response()->json([
                'warnings' => $warnings
            ]);
        }

        if (!$group->is_fit) {
            $bookingPreview = $this->getBookingPreview($group, $request);
            $minimumPayment = $bookingPreview->getMinimumPayment($bookingPreview->clients->first());

            if (($request->input('payment') < $minimumPayment)) {
                throw ValidationException::withMessages(['payment' => 'The minimum payment amount for this booking is $' . ceil($minimumPayment * 100) / 100 . '.']);
            }
        }

        if ($request->boolean('isBgCouple') == true) {
            Booking::where('group_id', $group->id)->update(['is_bg_couple' => false]);
        }

        $booking = $group->bookings()->create([
            'special_requests' => $request->input('specialRequests'),
            'notes' => $request->input('notes'),
            'is_bg_couple' => $request->boolean('isBgCouple'),
        ]);

        BookingRoomBlock::create([
            'booking_id' => $booking->id,
            'room_block_id' => $request->input('room'),
            'bed' => $request->input('bed'),
            'check_in' => $request->input('dates.start'),
            'check_out' => $request->input('dates.end'),
        ]);

        $client = Client::firstOrCreate(
            ['email' => $request->input('client.email')],
            [
                'first_name' => $request->input('client.firstName'),
                'last_name' => $request->input('client.lastName')
            ]
        );

        if (!$group->is_fit) {
            $address = $client->addresses()->firstOrCreate(
                ['zip_code' => $request->input('address.zipCode')],
                [
                    'country_id' => $request->input('address.country', 0) ? $request->input('address.country') : null,
                    'other_country' => $request->input('address.country', 0) ? null : $request->input('address.otherCountry'),
                    'state_id' => $request->input('address.country', 0) ? $request->input('address.state') : null,
                    'other_state' => $request->input('address.country', 0) ? null : $request->input('address.otherState'),
                    'city' => $request->input('address.city'),
                    'line_1' => $request->input('address.line1'),
                    'line_2' => $request->input('address.line2')
                ]
            );

            $card = $client->cards()->create([
                'name' => $request->input('card.name'),
                'number' => $request->input('card.number'),
                'type' => $request->input('card.type'),
                'expiration_date' => $request->input('card.expMonth') . $request->input('card.expYear'),
                'code' => $request->input('card.code'),
                'address_id' => $address->id
            ]);
        }

        $bookingClient = $booking->clients()->create([
            'client_id' => $client->id,
            'first_name' => $request->input('client.firstName'),
            'last_name' => $request->input('client.lastName'),
            'card_id' => $group->is_fit ? null : $card->id,
            'telephone' => $request->input('client.phone'),
            'insurance' => $request->input('insurance'),
            'insurance_signed_at' => now()
        ]);

        foreach ($request->input('guests') as $guest) {
            $bookingClient->guests()->create([
                'first_name' => $guest['firstName'],
                'last_name' => $guest['lastName'],
                'gender' => $guest['gender'],
                'birth_date' => $guest['birthDate'],
                'check_in' => $request->input('dates.start'),
                'check_out' => $request->input('dates.end'),
                'insurance' => $request->input('insurance'),
            ]);
        }

        if (!$group->is_fit) {
            $bookingClient->payments()->create([
                'card_id' => $card->id,
                'amount' => $request->input('payment'),
            ]);
        }

        return (new BookingResource($booking))->response()->setStatusCode(201);
    }

    protected function getBookingPreview($group, $request, $booking = null)
    {
        if (is_null($booking)) {
            $booking = Booking::make([
                'group_id' => $group->id,
            ]);
        }

        $roomBlocks = [];
        $bookingClients = [];
        $bookingGuests = [];
        $bookingClientGuests = [];
        $bookingClient = null;

        if ($booking->id) {
            $booking->special_requests = $request['booking']['specialRequests'] ?? null;

            foreach ($request['booking']['roomArrangements'] ?? [] as $arrangement) {
                $room_block = RoomBlock::with('room.hotel')->find($arrangement['room']);

                if ($room_block) {
                    $room_block->pivot = new Pivot([
                        'booking_id' => $booking->id,
                        'room_block_id' => $arrangement['room'],
                        'bed' => $arrangement['bed'],
                        'check_in' => Carbon::parse($arrangement['dates']['start']),
                        'check_out' => Carbon::parse($arrangement['dates']['end']),
                    ], $booking);

                    $roomBlocks[] = $room_block;
                }
            }

            foreach ($booking->clients as $originalClient) {
                $bookingClient = clone $originalClient;

                $clientGuestData = collect($request['guests'] ?? [])
                    ->where('client', $originalClient->id)
                    ->reject(fn($guest) => $guest['deleted_at'] ?? false);

                $clientGuests = [];

                foreach ($clientGuestData as $guest) {
                    $guestModel = Guest::make([
                        'booking_client_id' => $originalClient->id,
                        'first_name' => $guest['firstName'],
                        'last_name' => $guest['lastName'],
                        'gender' => $guest['gender'],
                        'birth_date' => Carbon::parse($guest['birthDate']),
                        'check_in' => Carbon::parse($guest['dates']['start']),
                        'check_out' => Carbon::parse($guest['dates']['end']),
                        'insurance' => $guest['insurance'] ?? null,
                        'transportation' => $guest['transportation'],
                        'transportation_type' => $guest['transportation'] ? ($guest['transportation_type'] ?? 1) : null,
                        'custom_group_airport' => $guest['transportation'] ? ($guest['customGroupAirport'] ?? null) : null,
                    ]);

                    $guestModel->id =  count($clientGuests) + 1;
                    $clientGuests[] = $guestModel;
                }

                $booking->setRelation('roomBlocks', new \Illuminate\Database\Eloquent\Collection($roomBlocks));
                $bookingClient->setRelation('guests', new \Illuminate\Database\Eloquent\Collection($clientGuests));

                $bookingClients[] = $bookingClient;
                $bookingGuests = array_merge($bookingGuests, $clientGuests);
            }
        } else {

            $booking->room_block_id = $request->input('room');
            $booking->check_in = $request->input('dates.start');
            $booking->check_out = $request->input('dates.end');

            $room_block = RoomBlock::where('id', $request->input('room'))->first();
            $booking->setRelation('roomBlocks', new \Illuminate\Database\Eloquent\Collection([$room_block]));

            $room_block->pivot = new Pivot([
                'bed' => $request->input('bed'),
                'check_in' => $request->input('dates.start'),
                'check_out' => $request->input('dates.end'),
            ], $booking);
            $guestCount = 0;

            $bookingClient = \App\Models\BookingClient::make([
                'first_name' => $request->input('client.firstName'),
                'last_name' => $request->input('client.lastName'),
            ]);

            foreach($request->input('guests') as $guest) {
                $guestCount++;

                $bookingClientGuest = \App\Models\Guest::make([
                    'birth_date' => $guest['birthDate'],
                    'check_in' => $request->input('dates.start'),
                    'check_out' => $request->input('dates.end'),
                    'insurance' => $request->input('insurance'),
                ]);
                $bookingClientGuest->id = $guestCount; // Dirty & Hacky

                array_push($bookingClientGuests, $bookingClientGuest);
                array_push($bookingGuests, $bookingClientGuest);
            }

            array_push($bookingClients, $bookingClient);

            $bookingClient->setRelation('guests', new \Illuminate\Database\Eloquent\Collection($bookingClientGuests));
        }

        $booking->setRelation('clients', new \Illuminate\Database\Eloquent\Collection($bookingClients));
        $booking->setRelation('guests', new \Illuminate\Database\Eloquent\Collection($bookingGuests));

        return $booking;
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Group $group
     * @param  \App\Models\Booking  $booking
     * @return \Illuminate\Http\Response
     */
    public function show(Group $group, Booking $booking)
    {
        $booking->load([
            'clients.guests.flight_manifest',
            'roomBlocks.hotel_block',
            'paymentArrangements',
        ]);

        $guestInOtherBookings = [];

        $booking->guests->each(function($currentGuest) use ($group, $booking, &$guestInOtherBookings) {
            $otherGuests = Guest::where('first_name', $currentGuest->first_name)
                ->where('last_name', $currentGuest->last_name)
                ->where('birth_date', $currentGuest->birth_date)
                ->where('id', '!=', $currentGuest->id)
                ->whereHas('booking_client.booking', fn($q) => $q->where('group_id', $group->id)->where('id', '!=', $booking->id))
                ->with('booking_client.booking:id,order')
                ->get()
                ->pluck('booking_client.booking');

            if (!empty($otherGuests)) {
                $guestInOtherBookings[$currentGuest->id] = $otherGuests;
            }
        });

        return (new BookingResource($booking))->additional([
            'group' => new GroupResource($group->load(['hotels.rooms', 'provider', 'attrition_image', 'airports.airport', 'airports.transfer', 'groupAttritionDueDates'])),
            'transportationTypes' => TransportationType::all(),
            'airports' => $group->destination->airports,
            'airlines' => Airline::orderBy('name', 'asc')->get(),
            'previousBooking' => Booking::select('id')->where('order', '<', $booking->order)->where('group_id', $group->id)->orderBy('order', 'desc')->first(),
            'nextBooking' => Booking::select('id')->where('order', '>', $booking->order)->where('group_id', $group->id)->orderBy('order', 'asc')->first(),
            'guestInOtherBookings' => $guestInOtherBookings,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \App\Models\Group $group
     * @param  \App\Models\Booking  $booking
     * @param  \App\Http\Requests\UpdateBooking  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Group $group, Booking $booking, UpdateBooking $request)
    {
        BookingRoomBlock::where('booking_id', $booking->id)->delete();

        if ($request->boolean('isBgCouple') == true && !$booking->is_bg_couple) {
            Booking::where('group_id', $group->id)->where('id', '!=', $booking->id)->update(['is_bg_couple' => false]);
        }

        foreach ($request->roomArrangements as $roomArrangement) {
            BookingRoomBlock::create([
                'booking_id' => $booking->id,
                'room_block_id' => $roomArrangement['room'],
                'bed' => $roomArrangement['bed'],
                'check_in' => $roomArrangement['dates']['start'],
                'check_out' => $roomArrangement['dates']['end'],
            ]);
        }

        $booking->deposit = $request->input('deposit');
        $booking->deposit_type = $request->input('depositType');
        $booking->booking_id = $request->input('bookingId');
        $booking->special_requests = $request->input('specialRequests');
        $booking->notes = $request->input('notes');
        $booking->is_bg_couple = $request->boolean('isBgCouple');
        $booking->total <= $booking->payment_total ? $booking->is_paid = true : $booking->is_paid = false;
        $booking->save();

        return new BookingResource($booking);
    }

    /**
     * Update the specified booking's guests in storage.
     *
     * @param \App\Models\Group $group
     * @param  \App\Models\Booking  $booking
     * @param  \App\Http\Requests\UpdateBookingGuests  $request
     * @return \Illuminate\Http\Response
     */
    public function updateGuests(Group $group, Booking $booking, UpdateBookingGuests $request, BookingService $bookingService)
    {
        $warnings = [];
        $duplicatesInRequest = collect();
        $guests = collect($request->validated()['guests']);

        foreach ($guests as $guest) {
            $duplicatesInRequestIndex = $guests->filter(function($g) use ($guest) {
                return empty($g['deleted_at'])
                    && $g['firstName'] === $guest['firstName']
                    && $g['lastName'] === $guest['lastName']
                    && (Carbon::parse($g['birthDate'], 'UTC')->format('Y-m-d')) === Carbon::parse($guest['birthDate'], 'UTC')->format('Y-m-d');
            })->keys();

            if ($duplicatesInRequestIndex->count() > 1) {
                $duplicatesInRequest = $duplicatesInRequest->merge($duplicatesInRequestIndex);
            }

            $duplicateGuests = Guest::whereHas('booking_client.booking', function ($query) use ($group, $booking) {
                    $query->where('group_id', $group->id)
                        ->where('id', '!=', $booking->id);
                })
                ->where('first_name', $guest['firstName'])
                ->where('last_name', $guest['lastName'])
                ->where('birth_date', Carbon::parse($guest['birthDate'], 'UTC')->format('Y-m-d'))
                ->get();

            if ($duplicateGuests->isNotEmpty()) {
                foreach($duplicateGuests as $duplicateGuest){
                    $warning = ucwords($duplicateGuest->first_name) . ' ' . ucwords($duplicateGuest->last_name) . ' is already booked in booking #'. $duplicateGuest->booking_client->booking->order;
                    if (!in_array($warning, $warnings)) {
                        $warnings[] = $warning;
                    }
                }
            }
        }

        if ($duplicatesInRequest->isNotEmpty()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'duplicate_guests_in_request' => $duplicatesInRequest->toArray(),
            ]);
        }

        $allGuests = collect($request->validated()['guests'])->filter(fn($guest) => empty($guest['deleted_at']));

        if ($booking->roomBlocks->isNotEmpty()) {
            $allRoomDates = $booking->roomBlocks->map(function ($roomBlock) {
                return [
                    'start' => Carbon::parse($roomBlock->pivot->check_in)->format('Y-m-d'),
                    'end' => Carbon::parse($roomBlock->pivot->check_out)->format('Y-m-d')
                ];
            });

            $earliestRoomStart = $allRoomDates->min('start');
            $latestRoomEnd = $allRoomDates->max('end');

            foreach ($allGuests as $guest) {
                $guestCheckIn = Carbon::parse($guest['dates']['start'])->format('Y-m-d');
                $guestCheckOut = Carbon::parse($guest['dates']['end'])->format('Y-m-d');

                if ($guestCheckIn < $earliestRoomStart || $guestCheckOut > $latestRoomEnd) {
                    $guestName = $guest['firstName'] . ' ' . $guest['lastName'];
                    $warnings[] = "Guest {$guestName}'s travel dates ({$guestCheckIn} to {$guestCheckOut}) are outside the room arrangements range ({$earliestRoomStart} to {$latestRoomEnd}). Please adjust room dates or guest travel dates.";
                }
            }
        }

        foreach ($booking->roomBlocks as $roomBlock) {
            $roomCheckIn = Carbon::parse($roomBlock->pivot->check_in)->format('Y-m-d');
            $roomCheckOut = Carbon::parse($roomBlock->pivot->check_out)->format('Y-m-d');

            $childRate = $roomBlock->child_rates->sortByDesc('to')->first();
            $childAge = $roomBlock->room->adults_only ? 17 : ($childRate ? $childRate->to : 17);
            $adultAge = $childAge + 1;

            $guests = $allGuests->filter(function ($guest) use ($roomCheckIn, $roomCheckOut) {
                $guestCheckIn = Carbon::parse($guest['dates']['start'])->format('Y-m-d');
                $guestCheckOut = Carbon::parse($guest['dates']['end'])->format('Y-m-d');

                return $guestCheckIn < $roomCheckOut && $guestCheckOut > $roomCheckIn;
            });

            if ($guests->isEmpty()) {
                continue;
            }

            $adults = $guests->filter(function ($guest) use ($group, $childAge) {
                return Carbon::parse($guest['birthDate'])->diffInYears($guest['dates']['start']) > $childAge;
            });

            $children = $guests->filter(function ($guest) use ($group, $childAge) {
                return Carbon::parse($guest['birthDate'])->diffInYears($guest['dates']['start']) <= $childAge;
            });

            if (!$roomBlock->room->adults_only && Carbon::parse($guests->first()['birthDate'])->diffInYears($guests->first()['dates']['start']) <= $childAge) {
                $warnings[] = "Room {$roomBlock->room->name}: Guest 1 must be an adult. Guests at-least {$adultAge} years old are considered adults.";
            }

            if ($roomBlock->room->adults_only && $children->count() > 0) {
                $warnings[] = "Room {$roomBlock->room->name} is for adults only. Guests at-least {$adultAge} years old are considered adults.";
            }

            if (!$roomBlock->room->adults_only && $adults->count() > $roomBlock->room->max_adults) {
                $warnings[] = "Room {$roomBlock->room->name} has a maximum limit of {$roomBlock->room->max_adults} adults. You have added {$adults->count()} adults. Guests at-least {$adultAge} years old are considered adults.";
            }

            if (!$roomBlock->room->adults_only && $children->count() > $roomBlock->room->max_children) {
                $warnings[] = "Room {$roomBlock->room->name} has a maximum limit of {$roomBlock->room->max_children} children. You have added {$children->count()} children. Guests upto {$childAge} years old are considered children.";
            }

            if ($guests->count() > $roomBlock->room->max_occupants) {
                $warnings[] = "Room {$roomBlock->room->name} has a maximum limit of {$roomBlock->room->max_occupants} guests. You have added {$guests->count()} guests.";
            }
        }

        if (!$request->ignoreGuestWarnings && count($warnings) > 0) {
            return response()->json([
                'warnings' => $warnings
            ]);
        }

        $guests = $bookingService->updateGuests($group, $booking, $request);

        return GuestResource::collection($guests);
    }

    public function updateTravelDates(Group $group, Booking $booking, Request $request, BookingService $bookingService)
    {
        $bookingService->updateTravelDates( $group, $booking, $request);

        return response()->json()->setStatusCode(204);
    }

    public function updateDeparturePickupTime(Group $group, Booking $booking, Request $request, BookingService $bookingService)
    {
        $bookingService->updateDeparturePickupTime($booking, $request);

        return response()->json()->setStatusCode(204);
    }

    public function updateFlightManifests(Group $group, Booking $booking, UpdateBookingFlightManifests $request, BookingService $bookingService)
    {
        $flightManifests = $request->input('flightManifests');

        $guests = $bookingService->updateFlightManifests($booking, $flightManifests);

        return GuestResource::collection($guests);
    }

    /**
     * Confirm the specified booking.
     *
     * @param \App\Models\Group $group
     * @param  \App\Models\Booking  $booking
     * @return \Illuminate\Http\Response
     */
     public function confirm(Group $group, Booking $booking, Request $request, BookingService $bookingService) {
        $this->authorize('confirm', $booking);

        $bookingService->confirm($group, $booking, $request);

        return response()->json()->setStatusCode(204);
     }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Group $group
     * @param  \App\Models\Booking  $booking
     * @return \Illuminate\Http\Response
     */
    public function destroy(Group $group, Booking $booking)
    {
        if (
            ($booking->group->is_fit && !$booking->booking_clients()->whereHas('fitQuotes')->exists()) ||
            (
                !$booking->group->is_fit &&
                is_null($booking->confirmed_at) &&
                !$booking->clients()->whereHas('payments', function ($query) { $query->where('confirmed_at', '!=', null); })->exists()
            )
        ) {
            $booking->trackedChanges()->delete();
            DB::table('booking_room_block')->where('booking_id', $booking->id)->delete();
            DB::table('bookings')->where('id', $booking->id)->delete();
        } else {
            $booking->delete();
        }

        // Send cancellation notifications to all clients after booking is deleted
        $this->sendCancellationNotifications($booking);

        return response()->json()->setStatusCode(204);
    }

    /**
     * Send cancellation notifications to all booking clients
     *
     * @param \App\Models\Booking $booking
     * @return void
     */
    protected function sendCancellationNotifications(Booking $booking)
    {
        // Load booking clients with their related data including the booking and group (even if trashed)
        $bookingClients = $booking->booking_clients()->with([
            'client',
            'bookingWithTrashed.group'
        ])->get();
        
        foreach ($bookingClients as $bookingClient) {
            // Skip if no client email
            if (!$bookingClient->client || !$bookingClient->client->email) {
                continue;
            }

            // Determine cancellation type based on date and insurance
            $cancellationType = $this->determineCancellationType($booking, $bookingClient);

            // Send notification to the client
            $bookingClient->client->notify(
                new BookingCancellation($bookingClient, $cancellationType)
            );
        }
    }

    /**
     * Determine the cancellation type based on cancellation date and insurance status
     *
     * @param \App\Models\Booking $booking
     * @param \App\Models\BookingClient $bookingClient
     * @return string
     */
    protected function determineCancellationType(Booking $booking, $bookingClient): string
    {
        $now = Carbon::now();
        $cancellationDate = $booking->cancellation_date ?? $booking->group->cancellation_date;
        $hasInsurance = (bool) $bookingClient->insurance;

        // Determine if cancellation is before or after the deadline
        $isBeforeCancellationDate = $cancellationDate && $now->lessThan($cancellationDate);

        if ($isBeforeCancellationDate) {
            return $hasInsurance 
                ? BookingCancellation::TYPE_BEFORE_WITH_INSURANCE 
                : BookingCancellation::TYPE_BEFORE_NO_INSURANCE;
        } else {
            return $hasInsurance 
                ? BookingCancellation::TYPE_AFTER_WITH_INSURANCE 
                : BookingCancellation::TYPE_AFTER_NO_INSURANCE;
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Group $group
     * @param  \App\Models\Booking  $booking
     * @return \Illuminate\Http\Response
     */
    public function restore(Group $group, Booking $booking)
    {
        $this->authorize('restore', $booking);

        $booking->roomBlocks()->where('is_active', false)->update(['is_active' => true]);
        $booking->restore();

        return response()->json()->setStatusCode(204);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Group $group
     * @param  \App\Models\Booking  $booking
     * @return \Illuminate\Http\Response
     */
    public function forceDestroy(Group $group, Booking $booking)
    {
        $this->authorize('forceDelete', $booking);

        $startOrder = $booking->order;

        $booking->trackedChanges()->delete();
        DB::table('booking_room_block')->where('booking_id', $booking->id)->delete();
        DB::table('bookings')->where('id', $booking->id)->delete();

        $ids = $group->bookings()->withTrashed()->ordered()->where('order', '>', $startOrder)->get('id')->pluck('id');

        Booking::setNewOrder($ids, $startOrder);

        return response()->json()->setStatusCode(204);
    }

    /**
     * Move the specified resource order up.
     *
     * @param \App\Models\Group $group
     * @param  \App\Models\Booking  $booking
     * @return \Illuminate\Http\Response
     */
    public function moveUp(Group $group, Booking $booking)
    {
        $booking->moveOrderUp();

        return response()->json()->setStatusCode(204);
    }

    /**
     * Move the specified resource order down.
     *
     * @param \App\Models\Group $group
     * @param  \App\Models\Booking  $booking
     * @return \Illuminate\Http\Response
     */
    public function moveDown(Group $group, Booking $booking)
    {
        $booking->moveOrderDown();

        return response()->json()->setStatusCode(204);
    }

    /**
     * Stream the booking's invoice to the browser.
     *
     * @param \App\Models\Group $group
     * @param  \App\Models\Booking  $booking
     * @return PDF
     */
    public function streamInvoice(Group $group, Booking $booking, Request $request)
    {
        $previewBooking = null;

        if ($request->has('preview')) {
            $previewData = json_decode($request->get('data'), true);
            $previewBooking = $this->getBookingPreview($group, $previewData, $booking);
        }

        $invoice = PDF::loadView('pdf.invoice', ['invoice' => $previewBooking->invoice ?? $booking->invoice ])->stream('R' . $booking->order . ' BB Invoice - ' . $group->name . '.pdf');
        $invoice->headers->set('X-Vapor-Base64-Encode', 'True');

        return $invoice;
    }

    public function streamTravelDocuments(Group $group, Booking $booking, BookingService $bookingService)
    {
        $url = $bookingService->streamTravelDocuments($group, $booking);

        return redirect()->away($url);
    }

    /**
     * Send booking's invoice to specified clients.
     *
     * @param \App\Models\Group $group
     * @param  \App\Models\Booking $booking
     * @return \Illuminate\Http\Response
     */
    public function sendInvoice(Group $group, Booking $booking, Request $request, BookingService $bookingService)
    {
        $bookingService->sendInvoice($booking, $request);

        return response()->json()->setStatusCode(204);
    }

    public function sendTravelDocuments(Group $group, Booking $booking, Request $request, BookingService $bookingService)
    {
        $bookingService->sendTravelDocuments($booking, $request);

        return response()->json()->setStatusCode(204);
    }

    public function getFlightDetails(Request $request)
    {
        $flights = $request->all();
        $flightDetails = [];

        foreach($flights as $flight) {
            if ($flight['set']) {
                $flightDetails[$flight['guestId']]['guest_name'] = $flight['guestName'];

                if (isset($flight['arrivalDateTime']) && isset($flight['arrivalAirport'])) {
                    $arrival_airport = Airport::find($flight['arrivalAirport']);
                    $departure_from = Carbon::parse($flight['arrivalDepartureDate'], $flight['arrivalDepartureAirportTimezone'])->startOfDay()->setTimezone('UTC')->format('Y-m-d\TH:i:s\Z');
                    $departure_to = Carbon::parse($flight['arrivalDepartureDate'], $flight['arrivalDepartureAirportTimezone'])->endOfDay()->setTimezone('UTC')->format('Y-m-d\TH:i:s\Z');

                    $response = Http::withHeaders([
                        'x-apikey' => config('services.aeroapi.api_key'),
                    ])->get(config('services.aeroapi.api_url') . "schedules/{$departure_from}/{$departure_to}", [
                        'origin' => $flight['arrivalDepartureAirportIata'],
                        'include_codeshares' => 'true',
                        'include_regional' => 'true',
                        'airline' => $flight['arrivalAirline'],
                        'flight_number' => $flight['arrivalNumber'],
                    ]);

                    $formatted_response = json_decode($response, true);

                    if (isset($formatted_response['status']) and $formatted_response['status'] === 429) {
                        return response()->json(['error' => 'Something went wrong. Please re-enter the flight information to try again.'], 404);
                    }

                    if (isset($formatted_response['status']) && $formatted_response['status'] === 400) {
                        return response()->json(['error' => 'The system cannot search the flights that are more than 1 year in the future.'], 404);
                    }

                    $scheduled_flights = $formatted_response['scheduled'];

                    if (count($scheduled_flights) > 0) {
                        foreach ($scheduled_flights as $arrival_flight) {
                            if ($arrival_airport->airport_code === $arrival_flight['destination_iata'] && $flight['arrivalAirline'] . $flight['arrivalNumber'] === $arrival_flight['ident_iata']) {
                                $arrival_flight_details = [
                                    'flight_iata' => $arrival_flight['ident_iata'],
                                    'airport_iata' => $arrival_flight['destination_iata'],
                                    'scheduled_arrival_utc' => Carbon::parse($arrival_flight['scheduled_in'], 'UTC')->format('Y-m-d H:i'),
                                    'scheduled_arrival' => Carbon::parse($arrival_flight['scheduled_in'], 'UTC')->setTimezone($arrival_airport->timezone)->format('m/d/Y H:i'),
                                    'scheduled_arrival_formatted' => Carbon::parse($arrival_flight['scheduled_in'], 'UTC')->setTimezone($arrival_airport->timezone)->format('Y-m-d H:i'),
                                ];

                                $flightDetails[$flight['guestId']]['arrival'] = $arrival_flight_details;

                                FlightManifest::where('guest_id', $flight['guestId'])->update([
                                    'arrival_datetime' => $arrival_flight_details['scheduled_arrival_utc'],
                                ]);

                                break;
                            }
                        }
                    }
                }

                if (isset($flight['departureDateTime']) && isset($flight['departureAirport'])) {
                    $departure_airport = Airport::find($flight['departureAirport']);
                    $departure_from = Carbon::parse($flight['departureDate'], $departure_airport->timezone)->startOfDay()->setTimezone('UTC')->format('Y-m-d\TH:i:s\Z');
                    $departure_to = Carbon::parse($flight['departureDate'], $departure_airport->timezone)->endOfDay()->setTimezone('UTC')->format('Y-m-d\TH:i:s\Z');

                    $response = Http::withHeaders([
                        'x-apikey' => config('services.aeroapi.api_key'),
                    ])->get(config('services.aeroapi.api_url') . "schedules/{$departure_from}/{$departure_to}", [
                        'origin' => $departure_airport->airport_code,
                        'include_codeshares' => 'true',
                        'include_regional' => 'true',
                        'airline' => $flight['departureAirline'],
                        'flight_number' => $flight['departureNumber'],
                    ]);

                    $formatted_response = json_decode($response, true);

                    if (isset($formatted_response['status']) and $formatted_response['status'] === 429) {
                        return response()->json(['error' => 'Something went wrong. Please re-enter the flight information to try again.'], 404);
                    }

                    if (isset($formatted_response['status']) && $formatted_response['status'] === 400) {
                        return response()->json(['error' => 'The system cannot search the flights that are more than 1 year in the future.'], 404);
                    }

                    $scheduled_flights = $formatted_response['scheduled'];

                    if (count($scheduled_flights) > 0) {
                        foreach ($scheduled_flights as $departure_flight) {
                            if ($departure_airport->airport_code === $departure_flight['origin_iata'] && $flight['departureAirline'] . $flight['departureNumber'] === $departure_flight['ident_iata']) {
                                $departure_flight_details = [
                                    'flight_iata' => $departure_flight['ident_iata'],
                                    'airport_iata' => $departure_flight['origin_iata'],
                                    'scheduled_departure_utc' => Carbon::parse($departure_flight['scheduled_out'], 'UTC')->format('Y-m-d H:i'),
                                    'scheduled_departure' => Carbon::parse($departure_flight['scheduled_out'], 'UTC')->setTimezone($departure_airport->timezone)->format('m/d/Y H:i'),
                                    'scheduled_departure_formatted' => Carbon::parse($departure_flight['scheduled_out'], 'UTC')->setTimezone($departure_airport->timezone)->format('Y-m-d H:i'),
                                ];

                                $flightDetails[$flight['guestId']]['departure'] = $departure_flight_details;

                                FlightManifest::where('guest_id', $flight['guestId'])->update([
                                    'departure_datetime' => $departure_flight_details['scheduled_departure_utc'],
                                ]);

                                break;
                            }
                        }
                    }
                }
            }
        }

        return response()->json($flightDetails);
    }

    public function updatePaymentArrangements(Request $request, Group $group, Booking $booking, BookingService $bookingService)
    {
        $bookingService->updatePaymentArrangements($group, $booking, $request);

        return response()->json()->setStatusCode(204);
    }

    public function sendFitQuote(Group $group, Booking $booking, Request $request, BookingService $bookingService)
    {
        $bookingService->sendFitQuote($booking, $request);

        return response()->json()->setStatusCode(204);
    }

    public function cancelFitQuote(Group $group, Booking $booking, Request $request, BookingService $bookingService)
    {
        $bookingService->cancelFitQuote($booking, $request);

        return response()->json()->setStatusCode(204);
    }
}
