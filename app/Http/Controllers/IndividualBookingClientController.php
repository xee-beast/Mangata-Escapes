<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingClient;
use App\Http\Requests\SyncExtras;
use App\Http\Requests\UpdateBookingClient;
use App\Http\Requests\UpdateBookingClientCard;
use App\Http\Resources\BookingClientResource;
use App\Http\Resources\BookingResource;
use App\Http\Resources\CardResource;
use App\Http\Resources\CountryResource;
use App\Models\Booking;
use App\Models\BookingClient;
use App\Models\Client;
use App\Models\Country;
use Illuminate\Http\Request;

class IndividualBookingClientController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(BookingClient::class, 'bookingClient');
    }

    public function index(Booking $individual_booking, Request $request)
    {
        return BookingClientResource::collection($individual_booking->clients->load('card'))->additional([
            'booking' => new BookingResource($individual_booking),
            'countries' => CountryResource::collection(Country::with('states')->whereHas('states')->orderBy('name')->get()),
            'can' => [
                'create' => $request->user()->can('create', [BookingClient::class, $individual_booking]),
            ]
        ]);
    }

    public function store(Booking $individual_booking, StoreBookingClient $request)
    {
        $client = Client::firstOrCreate(
            ['email' => $request->input('email')],
            [
                'first_name' => $request->input('firstName'),
                'last_name' => $request->input('lastName')
            ]
        );

        $bookingClient = $individual_booking->clients()->make([
            'client_id' => $client->id,
            'first_name' => $request->input('firstName'),
            'last_name' => $request->input('lastName'),
            'telephone' => $request->input('phone')
        ]);

        if ($request->input('hasPaymentInfo', false)) {
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

            $bookingClient->card()->associate($card);
        }

        $bookingClient->save();

        return response()->json(new BookingClientResource($bookingClient))->setStatusCode(201);
    }

    public function show(Booking $individual_booking, BookingClient $bookingClient)
    {
        return (new BookingClientResource($bookingClient->load(['client', 'extras', 'fitRate'])))->additional([
            'booking' => new BookingResource($individual_booking),
            'countries' => CountryResource::collection(Country::with('states')->whereHas('states')->orderBy('name')->get())
        ]);
    }

    public function update(Booking $individual_booking, BookingClient $bookingClient, UpdateBookingClient $request)
    {
        $client = Client::firstOrCreate(
            ['email' => $request->input('email')],
            [
                'first_name' => $request->input('firstName'),
                'last_name' => $request->input('lastName')
            ]
        );

        $bookingClient->fill([
            'client_id' => $client->id,
            'first_name' => $request->input('firstName'),
            'last_name' => $request->input('lastName'),
            'telephone' => $request->input('phone')
        ])->save();

        return new BookingClientResource($bookingClient);
    }

    public function updateCard(Booking $individual_booking, BookingClient $bookingClient, UpdateBookingClientCard $request)
    {
        $address = $bookingClient->client->addresses()->create([
            'zip_code' => $request->input('address.zipCode'),
            'country_id' => $request->input('address.country', 0) ? $request->input('address.country') : null,
            'other_country' => $request->input('address.country', 0) ? null : $request->input('address.otherCountry'),
            'state_id' => $request->input('address.country', 0) ? $request->input('address.state') : null,
            'other_state' => $request->input('address.country', 0) ? null : $request->input('address.otherState'),
            'city' => $request->input('address.city'),
            'line_1' => $request->input('address.line1'),
            'line_2' => $request->input('address.line2')
        ]);

        $card = $bookingClient->client->cards()->create([
            'name' => $request->input('card.name'),
            'number' => $request->input('card.number'),
            'type' => $request->input('card.type'),
            'expiration_date' => $request->input('card.expMonth') . $request->input('card.expYear'),
            'code' => $request->input('card.code'),
            'address_id' => $address->id
        ]);

        $bookingClient->card()->associate($card)->save();

        return new CardResource($card);
    }

    public function syncExtras(Booking $individual_booking, BookingClient $bookingClient, SyncExtras $request)
    {
        $extraSync = [];

        $bookingClient->fitRate()->updateOrCreate(
            ['booking_client_id' => $bookingClient->id],
            [
                'accommodation' => $request->input('fitRate.accommodation'),
                'insurance' => $request->input('fitRate.insurance')
            ]
        );

        foreach ($request->input('extras') as $newExtra) {
            $extra = $bookingClient->extras()->updateOrCreate(
                ['description' => $newExtra['description']],
                [
                    'price' => $newExtra['price'],
                    'quantity' => $newExtra['quantity']
                ]
            );

            array_push($extraSync, $extra->id);
        }

        $bookingClient->extras()->whereNotIn('id', $extraSync)->delete();
        $individual_booking->total <= $individual_booking->payment_total ? $individual_booking->is_paid = true : $individual_booking->is_paid = false;
        $individual_booking->save();

        return response()->json();
    }

    public function destroy(Booking $individual_booking, BookingClient $bookingClient)
    {
        $bookingClient->delete();

        return response()->json()->setStatusCode(204);
    }
}
