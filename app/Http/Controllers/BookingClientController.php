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
use App\Http\Resources\GroupResource;
use App\Models\Booking;
use App\Models\BookingClient;
use App\Models\Client;
use App\Models\Country;
use App\Models\Group;
use Illuminate\Http\Request;

class BookingClientController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(BookingClient::class, 'bookingClient');
    }

    /**
     * Display a listing of the resource.
     *
     * @param \App\Models\Group $group
     * @param \App\Models\Booking $booking
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Group $group, Booking $booking, Request $request)
    {
        return BookingClientResource::collection($booking->clients->load('card'))->additional([
            'group' => new GroupResource($group->load(['hotels.rooms', 'provider'])),
            'booking' => new BookingResource($booking),
            'countries' => CountryResource::collection(Country::with('states')->whereHas('states')->orderBy('name')->get()),
            'can' => [
                'create' => $request->user()->can('create', [BookingClient::class, $booking]),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Models\Group $group
     * @param  \App\Models\Booking $booking
     * @param  \App\Http\Requests\StoreClient $request
     * @return \Illuminate\Http\Response
     */
    public function store(Group $group, Booking $booking, StoreBookingClient $request)
    {
        $client = Client::firstOrCreate(
            ['email' => $request->input('email')],
            [
                'first_name' => $request->input('firstName'),
                'last_name' => $request->input('lastName')
            ]
        );

        $bookingClient = $booking->clients()->make([
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

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Group $group
     * @param \App\Models\Booking $booking
     * @param \App\Models\BookingClient $client
     * @return \Illuminate\Http\Response
     */
    public function show(Group $group, Booking $booking, BookingClient $bookingClient)
    {
        return (new BookingClientResource($bookingClient->load(['client', 'extras', 'fitRate'])))->additional([
            'group' => new GroupResource($group),
            'booking' => new BookingResource($booking),
            'countries' => CountryResource::collection(Country::with('states')->whereHas('states')->orderBy('name')->get())
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Models\Group $group
     * @param \App\Models\Booking $booking
     * @param \App\Models\BookingClient $client
     * @param \App\Http\Requests\UpdateClient $request
     * @return \Illuminate\Http\Response
     */
    public function update(Group $group, Booking $booking, BookingClient $bookingClient, UpdateBookingClient $request)
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

    /**
     * Update the card on file.
     *
     * @param  \App\Models\Group $group
     * @param \App\Models\Booking $booking
     * @param \App\Models\BookingClient $client
     * @param \App\Http\Requests\UpdateBookingClientCard $request
     * @return \Illuminate\Http\Response
     */
    public function updateCard(Group $group, Booking $booking, BookingClient $bookingClient, UpdateBookingClientCard $request)
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

    /**
     * Update the card on file.
     *
     * @param  \App\Models\Group $group
     * @param \App\Models\Booking $booking
     * @param \App\Models\BookingClient $client
     * @param \App\Http\Requests\SyncExtras $request
     * @return \Illuminate\Http\Response
     */
    public function syncExtras(Group $group, Booking $booking, BookingClient $bookingClient, SyncExtras $request)
    {
        $extraSync = [];

        if ($group->is_fit) {
            $bookingClient->fitRate()->updateOrCreate(
                ['booking_client_id' => $bookingClient->id],
                [
                    'accommodation' => $request->input('fitRate.accommodation'),
                    'insurance' => $request->input('fitRate.insurance')
                ]
            );
        }

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
        $booking->total <= $booking->payment_total ? $booking->is_paid = true : $booking->is_paid = false;
        $booking->save();

        return response()->json();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Group $group
     * @param \App\Models\Booking $booking
     * @param \App\Models\BookingClient $client
     * @return \Illuminate\Http\Response
     */
    public function destroy(Group $group, Booking $booking, BookingClient $bookingClient)
    {
        $bookingClient->delete();

        return response()->json()->setStatusCode(204);
    }
}
