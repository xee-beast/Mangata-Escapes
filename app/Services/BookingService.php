<?php

namespace App\Services;

use App\Events\BookingConfirmed;
use App\Models\Airport;
use App\Models\BookingPaymentDate;
use App\Models\FitQuote;
use App\Models\FlightManifest;
use App\Models\Guest;
use App\Notifications\FitQuoteCancelled;
use App\Notifications\FitQuoteMail;
use App\Notifications\InvoiceMail;
use App\Notifications\TravelDocumentsMail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Barryvdh\DomPDF\Facade\Pdf as FacadePdf;

class BookingService
{
    protected $domPdfFontService;

    public function __construct(DomPdfFontService $domPdfFontService)
    {
        $this->domPdfFontService = $domPdfFontService;
    }

    public function updateNotes($request, $booking) {
        $request->validate([
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $booking->update([
            'notes' => $request->input('notes'),
        ]);
    }

    public function updateTravelDates($group, $booking, $request)
    {
        $dates = $request->validate([
            'start' => 'required|date|before:end',
            'end' => 'required|date|after:start',
        ]);

        if (!$group) {
            $booking->check_in = $dates['start'];
            $booking->check_out = $dates['end'];
        }

        $booking->clients()->get()->each(function($client) use ($dates) {
            $client->guests()->update([
                'check_in' => Carbon::parse($dates['start'], 'UTC'),
                'check_out' => Carbon::parse($dates['end'], 'UTC'),
            ]);
        });

        $booking->total <= $booking->payment_total ? $booking->is_paid = true : $booking->is_paid = false;
        $booking->save();
    }

    public function updateGuests ($group, $booking, $request)
    {
        $existingGuests = Guest::withTrashed()->whereHas('booking_client', function ($query) use ($booking) {
            $query->where('booking_id', $booking->id);
        })->get();

        $guestSync = [];

        foreach ($request->input('guests') as $newGuest) {
            $guest = $existingGuests->firstWhere('id', $newGuest['id'] ?? 0) ?? new Guest;
            $deletedAt = $group ? 'deleted_at' : 'deletedAt';
            $transportation = $group ? $group->transportation : $booking->transportation;
            $transportationType = $group ? 'transportation_type' : 'transportationType';
            $airport = $group ? ($group->defaultAirport() ? $group->defaultAirport()->id : null) : null;

            if (array_key_exists($deletedAt, $newGuest)) {
                if ($newGuest[$deletedAt] == false) {
                    $guest->restore();
                } else {
                    array_push($guestSync, $guest->id);
                    $guest->delete();

                    continue;
                }
            }

            $guest->fill([
                'booking_client_id' => $newGuest['client'],
                'first_name' => $newGuest['firstName'],
                'last_name' => $newGuest['lastName'],
                'gender' => $newGuest['gender'],
                'birth_date' => $newGuest['birthDate'],
                'check_in' => $newGuest['dates']['start'],
                'check_out' => $newGuest['dates']['end'],
                'insurance' => $newGuest['insurance'] ?? null,
                'transportation' => $transportation ? $newGuest['transportation'] ?? false : false,
                'transportation_type' => ($transportation && $newGuest['transportation']) ? ($newGuest[$transportationType] ?? 1) : null,
                'custom_group_airport' => ($transportation && $newGuest['transportation']) ? ($newGuest['customGroupAirport'] ?? $airport) : null,
                'departure_pickup_time' => ($transportation && $newGuest['transportation']) ? ($newGuest['departurePickupTime'] ?? null) : null,
            ])->save();

            array_push($guestSync, $guest->id);
        }

        Guest::whereHas('booking_client', function ($query) use ($booking) {
            $query->where('booking_id', $booking->id);
        })->whereNotIn('id', $guestSync)->forceDelete();

        $booking->total <= $booking->payment_total ? $booking->is_paid = true : $booking->is_paid = false;
        $booking->save();

        $guests = Guest::withTrashed()->with('flight_manifest')->whereHas('booking_client', function ($query) use ($booking) {
            $query->where('booking_id', $booking->id);
        })->get();

        return $guests;
    }

    public function updateDeparturePickupTime($booking, $request)
    {
        $request->validate([
            'departurePickupTime' => 'required',
        ]);

        $booking->clients()->get()->each(function($client) use ($request) {
            $client->guests()->update([
                'departure_pickup_time' => $request->departurePickupTime,
            ]);
        });
    }

    public function updateFlightManifests($booking, $flightManifests)
    {
        foreach ($flightManifests as $index => $flightManifest) {
            if (!$booking->guests->contains('id', $flightManifest['guestId'])) {
                continue;
            }

            if (!$flightManifest['set']) {
                FlightManifest::where('guest_id', $flightManifest['guestId'])->delete();
                continue;
            }

            $arrival_departure_airport_iata = null;
            $arrival_departure_airport_timezone = null;

            if (isset($flightManifest['arrivalDepartureAirportIata']) && !empty($flightManifest['arrivalDepartureAirportIata'])) {
                $response = Http::withHeaders([
                    'x-apikey' => config('services.aeroapi.api_key'),
                ])->get(config('services.aeroapi.api_url') . "/airports/{$flightManifest['arrivalDepartureAirportIata']}");

                $arrival_departure_airport = $response->json();

                if (isset($arrival_departure_airport['status']) && $arrival_departure_airport['status'] === 429) {
                    throw ValidationException::withMessages([
                        "flightManifests.{$index}.arrivalDepartureAirportIata" => ['Api rate limit exceeded. Either increase the limit by upgrading the subscription plan or try again after 1 minute.']
                    ]);
                }

                if (!isset($arrival_departure_airport['code_iata']) || (isset($arrival_departure_airport['code_iata']) && $arrival_departure_airport['code_iata'] !== strtoupper($flightManifest['arrivalDepartureAirportIata']))) {
                    throw ValidationException::withMessages([
                        "flightManifests.{$index}.arrivalDepartureAirportIata" => ['The airport code is not valid.']
                    ]);
                } else {
                    $arrival_departure_airport_iata = $arrival_departure_airport['code_iata'];
                    $arrival_departure_airport_timezone = $arrival_departure_airport['timezone'];
                }
            }

            $arrival_airport = isset($flightManifest['arrivalAirport']) ? Airport::find($flightManifest['arrivalAirport']) : null;
            $departure_airport = isset($flightManifest['departureAirport']) ? Airport::find($flightManifest['departureAirport']) : null;

            FlightManifest::updateOrCreate(
                [
                    'guest_id' => $flightManifest['guestId']
                ],
                [
                    'phone_number' => isset($flightManifest['phoneNumber']) ? $flightManifest['phoneNumber'] : null,
                    'arrival_departure_airport_iata' => $arrival_departure_airport_iata,
                    'arrival_departure_airport_timezone' => $arrival_departure_airport_timezone,
                    'arrival_departure_date' => isset($flightManifest['arrivalDepartureDate']) ? $flightManifest['arrivalDepartureDate'] : null,
                    'arrival_airport_id' => isset($flightManifest['arrivalAirport']) ? $flightManifest['arrivalAirport'] : null,
                    'arrival_airline' => isset($flightManifest['arrivalAirline']) ? $flightManifest['arrivalAirline'] : null,
                    'arrival_number' => isset($flightManifest['arrivalNumber']) ? $flightManifest['arrivalNumber'] : null,
                    'arrival_datetime' => isset($flightManifest['arrivalDateTime']) ? Carbon::parse($flightManifest['arrivalDateTime'], isset($flightManifest['arrivalAirport']) ? $arrival_airport->timezone : 'UTC')->setTimezone('UTC')->format('Y-m-d H:i:s') : null,
                    'departure_airport_id' => isset($flightManifest['departureAirport']) ? $flightManifest['departureAirport'] : null,
                    'departure_date' => isset($flightManifest['departureDate']) ?  $flightManifest['departureDate'] : null,
                    'departure_airline' => isset($flightManifest['departureAirline']) ? $flightManifest['departureAirline'] : null,
                    'departure_number' => isset($flightManifest['departureNumber']) ? $flightManifest['departureNumber'] : null,
                    'departure_datetime' => isset($flightManifest['departureDateTime']) ? Carbon::parse($flightManifest['departureDateTime'], isset($flightManifest['departureAirport']) ? $departure_airport->timezone : 'UTC')->setTimezone('UTC')->format('Y-m-d H:i:s') : null,
                ]
            );
        }

        $guests = Guest::withTrashed()->with('flight_manifest')->whereHas('booking_client', function ($query) use ($booking) {
            $query->where('booking_id', $booking->id);
        })->get();

        return $guests;
    }

    public function updatePaymentArrangements($group, $booking, $request)
    {
        $request->validate([
            'paymentArrangements' => 'sometimes|array',
            'paymentArrangements.*.dueDate' => 'required|date|after_or_equal:today',
            'paymentArrangements.*.amount' => 'required|numeric|min:1',
            'paymentArrangements.*.bookingClientId' => 'required',
        ]);

        $booking->paymentArrangements()->delete();

        if ($request->paymentArrangements) {
            foreach ($request->paymentArrangements as $paymentArrangement) {
                BookingPaymentDate::create([
                    'booking_id' => $booking->id,
                    'group_id' => $group ? $group->id : null,
                    'booking_client_id' => $paymentArrangement['bookingClientId'],
                    'due_date' => $paymentArrangement['dueDate'],
                    'amount' => $paymentArrangement['amount'],
                ]);
            }
        }
    }

    public function sendFitQuote($booking, $request)
    {
        $request->validate([
            'clients' => 'required|array',
            'expiryDateTime' => 'required|date|after:today',
        ]);

        $bookingClients = $booking->clients()->whereIn('id', $request->input('clients'))->get();

        foreach ($bookingClients as $bookingClient) {
            if (!$bookingClient->fitRate()->exists()) {
                throw ValidationException::withMessages([
                    "expiryDateTime" => [
                        "{$bookingClient->first_name} {$bookingClient->last_name} is missing FIT rates."
                    ]
                ]);
            }

            if ($bookingClient->pendingFitQuote()->exists()) {
                throw ValidationException::withMessages([
                    "expiryDateTime" => [
                        "Quote is already sent to {$bookingClient->first_name} {$bookingClient->last_name}."
                    ]
                ]);
            }

            if ($bookingClient->acceptedFitQuote()->exists()) {
                throw ValidationException::withMessages([
                    "expiryDateTime" => [
                        "Quote is already accepted by {$bookingClient->first_name} {$bookingClient->last_name}."
                    ]
                ]);
            }

            $fitQuote = $bookingClient->fitQuotes()->create([
                'expiry_date_time' => Carbon::parse($request->input('expiryDateTime')),
            ]);

            $bookingClient->client->notify(new FitQuoteMail($booking, $fitQuote));
        }
    }

    public function cancelFitQuote($booking, $request)
    {
        $fitQuote = FitQuote::find($request->fitQuoteId);
        $fitQuote->is_cancelled = true;
        $fitQuote->save();

        $fitQuote->bookingClient->client->notify(new FitQuoteCancelled($booking, $fitQuote));
    }

    public function sendInvoice($booking, $request)
    {
        $bookingClients = $booking->clients()->whereIn('id', $request->input('clients'))->get();

        $bookingClients->each(function ($bookingClient) use ($booking) {
            $bookingClient->client->notify(new InvoiceMail($booking));
        });
    }

    public function streamTravelDocuments($group, $booking)
    {
        if (app()->environment() !== 'local') {
            $this->domPdfFontService->ensureFontsArePrepared();
        }

        $guests = $booking->guests->filter(fn ($guest) => $guest->flight_manifest && $guest->transportation);

        $grouped_guests = $guests->groupBy(function ($guest) {
            $manifest = $guest->flight_manifest;

            return md5(json_encode([
                'arrival_datetime' => optional($manifest->arrival_datetime)->format('Y-m-d H:i'),
                'departure_datetime' => optional($manifest->departure_datetime)->format('Y-m-d H:i'),
                'arrival_airline' => $manifest->arrival_airline,
                'arrival_number' => $manifest->arrival_number,
                'departure_airline' => $manifest->departure_airline,
                'departure_number' => $manifest->departure_number,
                'arrival_airport' => optional($manifest->arrivalAirport)->id,
                'departure_airport' => optional($manifest->departureAirport)->id,
                'pickup_time' => $guest->departure_pickup_time,
            ]));
        });

        $processedGuests = $this->handelTravelDocumentsDuplicateGuests($group, $booking);

        $pdf = FacadePdf::loadView('pdf.travel-documents', [
            'group' => $group ? $group->load('provider', 'destination', 'airports.transfer') : null,
            'booking' => $booking->load('guests.flight_manifest', 'roomArrangements', 'roomBlocks.room.hotel', 'provider', 'destination.image', 'transfer'),
            'hotel' => $group ? $booking->roomBlocks->first()->hotel_block->hotel : null,
            'grouped_guests' => $grouped_guests,
            'processed_guests' => $processedGuests,
        ]);

        $filename = 'Travel Documents - ' . ($group ? $group->name : $booking->full_name) . ' - ' . $booking->order . '.pdf';
        $path = 'pdfs/' . $filename;
        \Storage::disk('s3')->put($path, $pdf->output());

        $url = \Storage::disk('s3')->temporaryUrl(
            $path,
            now()->addMinutes(5),
            [
                'ResponseContentDisposition' => 'inline; filename="' . $filename . '"',
                'ResponseContentType' => 'application/pdf',
            ]
        );

        return $url;
    }

    public function handelTravelDocumentsDuplicateGuests($group, $booking){
        $processedGuests = [];

        foreach ($booking->guests as $guest) {
            $guestData = [
                'guest' => $guest,
                'is_duplicate' => false,
                'min_check_in' => $guest->check_in,
                'max_check_out' => $guest->check_out,
                'duplicate_details' => [],
            ];

            if ($group) {
                $duplicateGuests = Guest::where('first_name', $guest->first_name)
                    ->where('last_name', $guest->last_name)
                    ->where('birth_date', $guest->birth_date->format('Y-m-d'))
                    ->whereHas('booking_client.booking', fn($q) => $q->where('group_id', $group->id))
                    ->with(['booking_client.booking.clients', 'booking_client.booking.roomBlocks.room'])
                    ->get();

                if ($duplicateGuests->count() > 1) {
                    $guestData['is_duplicate'] = true;
                    $guestData['min_check_in'] = $duplicateGuests->min('check_in');
                    $guestData['max_check_out'] = $duplicateGuests->max('check_out');

                    foreach ($duplicateGuests as $dupGuest) {
                        $dupBooking = $dupGuest->booking_client->booking;
                        $allClientsArray = [];

                        foreach ($dupBooking->clients as $client) {
                            $allClientsArray[] = $client->first_name . ' ' . $client->last_name . '\'s';
                        }

                        $count = count($allClientsArray);
                        $head = array_slice($allClientsArray, 0, -1);
                        $last = $allClientsArray[$count - 1];
                        $clientNames = $head ? implode(', ', $head) . ' & ' . $last : $last;

                        $guestData['duplicate_details'][] = [
                            'guest_name' => $guest->name,
                            'client_names' => $clientNames,
                            'check_in' => $dupGuest->check_in,
                            'check_out' => $dupGuest->check_out,
                        ];
                    }
                }
            }

            $processedGuests[] = $guestData;
        }

        return $processedGuests;
    }


    public function sendTravelDocuments($booking, $request)
    {
        if (app()->environment() !== 'local') {
            $this->domPdfFontService->ensureFontsArePrepared();
        }

        $bookingClients = $booking->clients()->whereIn('id', $request->input('clients'))->get();

        $bookingClients->each(function ($bookingClient) use ($booking) {
            $bookingClient->client->notify(new TravelDocumentsMail($booking));
        });
    }

    public function confirm($group, $booking, $request) {
        $booking->update([
            'confirmed_at' => now()
        ]);

        if (!$group || ($group && $group->is_active)) {
            event(new BookingConfirmed($booking, $request->input('sendEmail', false)));
        }
    }
}
