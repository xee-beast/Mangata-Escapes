<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\GetImages;
use App\Http\Requests\SendBulkIndividualBookingsEmail;
use App\Http\Requests\StoreIndividualBooking;
use App\Http\Requests\UpdateBookingFlightManifests;
use App\Http\Requests\UpdateIndividualBooking;
use App\Http\Requests\UpdateIndividualBookingGuests;
use App\Http\Requests\UpdateRoomArrangements;
use App\Http\Requests\UpdateBookingDueDates;
use App\Http\Resources\BookingResource;
use App\Http\Resources\GuestResource;
use App\Models\Airline;
use App\Models\Airport;
use App\Models\Booking;
use App\Models\BookingDueDate;
use App\Models\Client;
use App\Models\Destination;
use App\Models\Provider;
use App\Models\RoomArrangement;
use App\Models\Transfer;
use App\Models\TransportationType;
use App\Models\TravelAgent;
use App\Services\BookingService;
use App\Notifications\IndividualBookingBulkEmail;
use Carbon\Carbon;
use PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IndividualBookingController extends Controller
{
    use GetImages;

    public function __construct()
    {
        $this->authorizeResource(Booking::class, 'individual_booking');
    }

    public function index(Request $request)
    {
        $search = $request->query('search', '');
        $year = $request->query('year', '');
        $agent = $request->query('agent', '');
        $provider = $request->query('provider', '');

        if (!empty($search)) {
            $booking = Booking::withTrashed()
                ->whereNull('group_id')
                ->whereHas('clients', function($query) use ($search) {
                    $query->where('reservation_code', $search);
                })->first();

            if (!empty($booking)) {
                return [
                    'booking_id' => $booking->id,
                ];
            }
        }

        $bookings = Booking::withTrashed()
            ->whereNull('group_id')
            ->ordered()
            ->with([
                'roomArrangements',
                'clients.guests',
                'clients.card',
                'clients.payments',
                'clients.pendingFitQuote',
                'clients.acceptedFitQuote',
                'clients.discardedFitQuote',
                'trackedChanges',
                'paymentArrangements',
                'destination',
                'travel_agent',
                'provider',
                'transfer',
            ]);
        
        if(!empty($agent)) {
            $bookings->where('travel_agent_id', $agent);
        }

        if(!empty($provider)) {
            $bookings->where('provider_id', $provider);
        }

        if ('false' == $request->query('old', false)) {
            $bookings->whereDate('check_in', '>', Carbon::now());
        }

        if(!empty($year)) {
            $bookings->whereYear('check_in', $year);
        }

        if(!empty($search)) {
            $bookings->where(function ($query) use ($search) {
                $query->where('id_at_provider', 'LIKE', $search . '%')
                    ->orWhere(DB::raw("CONCAT(reservation_leader_first_name, ' ', reservation_leader_last_name)"), 'LIKE', '%' . $search . '%')
                    ->orWhereHas('clients', function($query) use ($search) {
                        $query->whereHas('client', function ($query) use ($search) {
                                $query->where('email', $search)
                                    ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', '%' . $search . '%');
                            })
                            ->orWhereHas('guests', function ($query) use ($search) {
                                $query->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', '%' . $search . '%');
                            });
                    })
                    ->orWhereHas('roomArrangements', function($query) use ($search) {
                        $query->where('hotel', 'LIKE', '%' . $search . '%')
                            ->orWhere('room', 'LIKE', '%' . $search . '%');
                    });
            });
        }

        $bookings->orderBy('check_in');

        return BookingResource::collection($bookings->paginate($request->query('paginate', 25)))
            ->additional([
                'can' => [
                    'create' => $request->user()->can('create', Booking::class),
                ],
                'agents' => TravelAgent::active()->select(
                    'travel_agents.id AS value',
                    DB::raw("CONCAT(travel_agents.first_name, ' ', travel_agents.last_name) AS text")
                )->get(),
                'providers' => Provider::select(
                    'providers.id AS value',
                    'providers.name AS text'
                )->get(),
                'years' => Booking::selectRaw('DISTINCT YEAR(check_in) as year')
                    ->withTrashed()
                    ->whereNull('group_id')
                    ->where(function($query) use ($request) {
                        if ('false' == $request->query('old', false)) {
                            $query->whereDate('check_in', '>', Carbon::now());
                        }
                    })
                    ->orderBy('year', 'desc')
                    ->pluck('year')
                    ->map(function($year) {
                        return ['value' => $year, 'text' => $year];
                    }),
            ]);
    }

    public function updateNotes(Request $request, Booking $booking, BookingService $bookingService)
    {
        $bookingService->updateNotes($request, $booking);

        return response()->json(['notes' => $booking->notes], 200);
    }
    public function store(StoreIndividualBooking $request)
    {
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

        $booking = Booking::create([
            'hotel_assistance' => $request->input('hotelAssistance'),
            'hotel_preferences' => $request->input('hotelAssistance') ? $request->input('hotelPreferences') : null,
            'hotel_name' => $request->input('hotelAssistance') ? null : $request->input('hotelName'),
            'room_category' => $request->input('roomCategory'),
            'room_category_name' => $request->input('roomCategory') ? $request->input('roomCategoryName') : null,
            'check_in' => $request->input('dates.start'),
            'check_out' => $request->input('dates.end'),
            'special_requests' => $request->input('specialRequests'),
            'notes' => $request->input('notes'),
            'budget' => $request->input('budget'),
            'transportation' => $request->input('transportation'),
            'departure_gateway' => $request->input('transportation') ? $request->input('departureGateway') : null,
            'flight_preferences' => $request->input('transportation') ? $request->input('flightPreferences') : null,
            'airline_membership_number' => $request->input('transportation') ? $request->input('airlineMembershipNumber') : null,
            'known_traveler_number' => $request->input('transportation') ? $request->input('knownTravelerNumber') : null,
            'flight_message' => $request->input('transportation') ? $request->input('flightMessage') : null,
            'email' => $request->input('client.email'),
            'reservation_leader_first_name' => $request->input('client.firstName'),
            'reservation_leader_last_name' => $request->input('client.lastName'),
        ]);

        $client = Client::firstOrCreate(
            ['email' => $request->input('client.email')],
            [
                'first_name' => $request->input('client.firstName'),
                'last_name' => $request->input('client.lastName')
            ]
        );

        $bookingClient = $booking->clients()->create([
            'client_id' => $client->id,
            'first_name' => $request->input('client.firstName'),
            'last_name' => $request->input('client.lastName'),
            'card_id' => null,
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
                'transportation' => $request->input('transportation'),
                'transportation_type' => $request->input('transportation') ? 1 : null,
                'custom_group_airport' => null,
            ]);
        }

        return (new BookingResource($booking))->response()->setStatusCode(201);
    }

    public function show(Booking $individual_booking)
    {
        return (new BookingResource($individual_booking->load([
            'bookingDueDates',
            'roomArrangements', 
            'clients.guests.flight_manifest',
            'paymentArrangements',
            'transfer',
            'destination.airports',
            'travel_agent',
            'provider',
            'travel_docs_cover_image',
            'travel_docs_image_two',
            'travel_docs_image_three',
        ])))->additional([
            'previousBooking' => Booking::whereNull('group_id')->select('id')->where('order', '<', $individual_booking->order)->orderBy('order', 'desc')->first(),
            'nextBooking' => Booking::whereNull('group_id')->select('id')->where('order', '>', $individual_booking->order)->orderBy('order', 'asc')->first(),
            'transportationTypes' => TransportationType::all(),
            'airlines' => Airline::orderBy('name', 'asc')->get(),
            'airports' => Airport::orderBy('airport_code', 'asc')->get(),
            'transfers' => Transfer::all(),
            'destinations' => Destination::select(
                'destinations.id AS value',
                DB::raw("CONCAT(destinations.name, ', ', countries.name) AS text"))->join('countries', 'destinations.country_id', '=', 'countries.id'
            )->get(),
            'agents' => TravelAgent::active()->select(
                'travel_agents.id AS value',
                DB::raw("CONCAT(travel_agents.first_name, ' ', travel_agents.last_name) AS text")
            )->get(),
            'providers' => Provider::select(
                'providers.id AS value',
                'providers.name AS text'
            )->get(),
        ]);
    }

    public function update(Booking $individual_booking, UpdateIndividualBooking $request)
    {
        $individual_booking->hotel_assistance = $request->input('hotelAssistance');
        $individual_booking->hotel_preferences = $request->input('hotelAssistance') ? $request->input('hotelPreferences') : null;
        $individual_booking->hotel_name = $request->input('hotelAssistance') ? null : $request->input('hotelName');
        $individual_booking->room_category = $request->input('roomCategory');
        $individual_booking->room_category_name = $request->input('roomCategory') ? $request->input('roomCategoryName') : null;
        $individual_booking->check_in = $request->input('dates.start');
        $individual_booking->check_out = $request->input('dates.end');
        $individual_booking->budget = $request->input('budget');
        $individual_booking->special_requests = $request->input('specialRequests');
        $individual_booking->notes = $request->input('notes');
        $individual_booking->transportation = $request->input('transportation');
        $individual_booking->departure_gateway = $request->input('transportation') ? $request->input('departureGateway') : null;
        $individual_booking->flight_preferences = $request->input('transportation') ? $request->input('flightPreferences') : null;
        $individual_booking->airline_membership_number = $request->input('transportation') ? $request->input('airlineMembershipNumber') : null;
        $individual_booking->known_traveler_number = $request->input('transportation') ? $request->input('knownTravelerNumber') : null;
        $individual_booking->flight_message = $request->input('transportation') ? $request->input('flightMessage') : null;
        $individual_booking->transportation_type = $request->input('transportation') ? $request->input('transportationType') : null;
        $individual_booking->transportation_submit_before = $request->input('transportation') ? $request->input('transportationSubmitBefore') : null;
        $individual_booking->transfer_id = $request->input('transportation') ? $request->input('transfer') : null;
        $individual_booking->destination_id = $request->input('destination');
        $individual_booking->email = $request->input('email');
        $individual_booking->reservation_leader_first_name = $request->input('reservationLeaderFirstName');
        $individual_booking->reservation_leader_last_name = $request->input('reservationLeaderLastName');
        $individual_booking->deposit = $request->input('deposit');
        $individual_booking->deposit_type = $request->input('depositType');
        $individual_booking->travel_agent_id = $request->input('agent');
        $individual_booking->provider_id = $request->input('provider');
        $individual_booking->id_at_provider = $request->input('providerId');
        $individual_booking->change_fee_date = $request->input('changeFeeDate');
        $individual_booking->change_fee_amount = $request->input('changeFeeAmount');
        $individual_booking->staff_message = $request->input('staffMessage');

        if (is_array($request->input('travelDocsCoverImage'))) {
            $individual_booking->travel_docs_cover_image()->associate($this->getImage($request->input('travelDocsCoverImage'))->id);
        } else {
            $individual_booking->travel_docs_cover_image()->dissociate();
        }

        if (is_array($request->input('travelDocsImageTwo'))) {
            $individual_booking->travel_docs_image_two()->associate($this->getImage($request->input('travelDocsImageTwo'))->id);
        } else {
            $individual_booking->travel_docs_image_two()->dissociate();
        }

        if (is_array($request->input('travelDocsImageThree'))) {
            $individual_booking->travel_docs_image_three()->associate($this->getImage($request->input('travelDocsImageThree'))->id);
        } else {
            $individual_booking->travel_docs_image_three()->dissociate();
        }

        $individual_booking->booking_id = $request->input('bookingId');
        $individual_booking->total <= $individual_booking->payment_total ? $individual_booking->is_paid = true : $individual_booking->is_paid = false;
        $individual_booking->save();

        return new BookingResource($individual_booking->loadMissing(['destination.airports', 'travel_docs_cover_image', 'travel_docs_image_two', 'travel_docs_image_three']));
    }

    public function updateTravelDates(Booking $individual_booking, Request $request, BookingService $bookingService)
    {
        $bookingService->updateTravelDates(null, $individual_booking, $request);

        return response()->json()->setStatusCode(204);
    }

    public function syncBookingDueDates(UpdateBookingDueDates $request, Booking $individual_booking)
    {
        $individual_booking->balance_due_date = $request->input('balanceDueDate');
        $individual_booking->cancellation_date = $request->input('cancellationDate');
        $individual_booking->save();

        $bookingDueDates = [];

        foreach ($request->input('dueDates') as $dueDate) {
            $bookingDueDate = $individual_booking->bookingDueDates()->updateOrCreate(
                [
                    'booking_id' => $individual_booking->id,
                    'date' => Carbon::parse($dueDate['date'])->format('Y-m-d')
                ],
                [
                    'amount' => $dueDate['amount'],
                    'type' => $dueDate['type']
                ]
            );

            array_push($bookingDueDates, $bookingDueDate->id);
        }

        BookingDueDate::where('booking_id', $individual_booking->id)->whereNotIn('id', $bookingDueDates)->delete();

        return response()->json()->setStatusCode(204);
    }

    public function updateRoomArrangements(Booking $individual_booking, UpdateRoomArrangements $request)
    {
        $individual_booking->roomArrangements()->delete();
        
        foreach ($request->roomArrangements as $roomArrangement) {
            RoomArrangement::create([
                'booking_id' => $individual_booking->id,
                'hotel' => $roomArrangement['hotel'],
                'room' => $roomArrangement['room'],
                'bed' => $roomArrangement['bed'],
                'check_in' => $roomArrangement['dates']['start'],
                'check_out' => $roomArrangement['dates']['end'],
            ]);
        }

        $individual_booking->total <= $individual_booking->payment_total ? $individual_booking->is_paid = true : $individual_booking->is_paid = false;
        $individual_booking->save();

        return response()->json();
    }

    public function updateGuests(Booking $individual_booking, UpdateIndividualBookingGuests $request, BookingService $bookingService)
    {
        $warnings = [];
        $duplicatesInRequest = collect();
        $guests = collect($request->validated()['guests'])->filter(fn($guest) => empty($guest['deletedAt']));

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

        if (Carbon::parse($guests->first()['birthDate'])->diffInYears($guests->first()['dates']['start']) <= 17) {
            $warnings[] = "Guest 1 must be an adult.";
        }

        if (!$request->ignoreGuestWarnings && count($warnings) > 0) {
            return response()->json([
                'warnings' => $warnings
            ]);
        }

        $guests = $bookingService->updateGuests(null, $individual_booking, $request);

        return GuestResource::collection($guests);
    }

    public function updateDeparturePickupTime(Booking $individual_booking, Request $request, BookingService $bookingService)
    {
        $bookingService->updateDeparturePickupTime($individual_booking, $request);

        return response()->json()->setStatusCode(204);
    }

    public function updateFlightManifests(Booking $individual_booking, UpdateBookingFlightManifests $request, BookingService $bookingService)
    {
        $flightManifests = $request->input('flightManifests');

        $guests = $bookingService->updateFlightManifests($individual_booking, $flightManifests);

        return GuestResource::collection($guests);
    }

    public function updatePaymentArrangements(Request $request, Booking $individual_booking, BookingService $bookingService)
    {
        $bookingService->updatePaymentArrangements(null, $individual_booking, $request);

        return response()->json()->setStatusCode(204);
    }

    public function confirm(Booking $individual_booking, Request $request, BookingService $bookingService) {
        $this->authorize('confirm', $individual_booking);

        $bookingService->confirm(null, $individual_booking, $request);

        return response()->json()->setStatusCode(204);
    }

    public function destroy(Booking $individual_booking)
    {
        if (!$individual_booking->group && !$individual_booking->booking_clients()->whereHas('fitQuotes')->exists()) {
            $individual_booking->trackedChanges()->delete();
            DB::table('bookings')->where('id', $individual_booking->id)->delete();

            if ($individual_booking->travel_docs_cover_image()->exists()) {
                $individual_booking->travel_docs_cover_image->delete();
            }

            if ($individual_booking->travel_docs_image_two()->exists()) {
                $individual_booking->travel_docs_image_two->delete();
            }

            if ($individual_booking->travel_docs_image_three()->exists()) {
                $individual_booking->travel_docs_image_three->delete();
            }
        } else {
            $individual_booking->delete();
        }

        return response()->json()->setStatusCode(204);
    }

    public function restore(Booking $individual_booking)
    {
        $this->authorize('restore', $individual_booking);

        $individual_booking->restore();

        return response()->json()->setStatusCode(204);
    }

    public function forceDestroy(Booking $individual_booking)
    {
        $this->authorize('forceDelete', $individual_booking);

        $startOrder = $individual_booking->order;

        $individual_booking->trackedChanges()->delete();
        DB::table('bookings')->where('id', $individual_booking->id)->delete();

        $ids = Booking::withTrashed()->whereNull('group_id')->ordered()->where('order', '>', $startOrder)->get('id')->pluck('id');
        Booking::setNewOrder($ids, $startOrder);

        if ($individual_booking->travel_docs_cover_image()->exists()) {
            $individual_booking->travel_docs_cover_image->delete();
        }

        if ($individual_booking->travel_docs_image_two()->exists()) {
            $individual_booking->travel_docs_image_two->delete();
        }

        if ($individual_booking->travel_docs_image_three()->exists()) {
            $individual_booking->travel_docs_image_three->delete();
        }

        return response()->json()->setStatusCode(204);
    }

    public function sendBulkEmail(SendBulkIndividualBookingsEmail $request)
    {
        $bookings = Booking::whereNull('group_id')->whereDate('check_in', '>', Carbon::now());

        $bookings->chunk(100, function($collection) use ($request) {
            foreach ($collection as $booking) {
                $booking->notify(new IndividualBookingBulkEmail($booking, $request->validated()));
            }
        });

        return response()->json()->setStatusCode(204);
    }

    public function updateTermsConditions(Request $request, Booking $individual_booking)
    {
        $request->validate([
            'termsAndConditions' => 'nullable|string',
        ]);

        $terms = $request->input('termsAndConditions');
        $stripped = strip_tags($terms);

        $individual_booking->terms_and_conditions = (trim($stripped) === '') ? $individual_booking->getDefaultTerms() : $terms;
        $individual_booking->save();

        return response()->json([
            'termsAndConditions' => $individual_booking->terms_and_conditions
        ], 200);    
    }

    public function sendFitQuote(Booking $individual_booking, Request $request, BookingService $bookingService)
    {
        $bookingService->sendFitQuote($individual_booking, $request);        

        return response()->json()->setStatusCode(204);
    }

    public function cancelFitQuote(Booking $individual_booking, Request $request, BookingService $bookingService)
    {
        $bookingService->cancelFitQuote($individual_booking, $request);

        return response()->json()->setStatusCode(204);
    }

    public function streamInvoice(Booking $individual_booking)
    {
        $invoice = PDF::loadView('pdf.invoice', ['invoice' => $individual_booking->invoice])->stream('R' . $individual_booking->order . ' BB Invoice - ' . $individual_booking->full_name . '.pdf');

        $invoice->headers->set('X-Vapor-Base64-Encode', 'True');

        return $invoice;
    }

    public function sendInvoice(Booking $individual_booking, Request $request, BookingService $bookingService)
    {
        $bookingService->sendInvoice($individual_booking, $request);

        return response()->json()->setStatusCode(204);
    }

    public function streamTravelDocuments(Booking $individual_booking, BookingService $bookingService)
    {
        $url = $bookingService->streamTravelDocuments(null, $individual_booking);

        return redirect()->away($url);
    }

    public function sendTravelDocuments(Booking $individual_booking, Request $request, BookingService $bookingService)
    {
        $bookingService->sendTravelDocuments($individual_booking, $request);

        return response()->json()->setStatusCode(204);
    }
}
