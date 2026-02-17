<?php

namespace App\Http\Controllers;

use App\Events\CardDeclined;
use App\Events\PaymentConfirmed;
use App\Http\Requests\DeletePayment;
use App\Http\Requests\StorePayment;
use App\Http\Requests\UpdatePayment;
use App\Http\Resources\BookingResource;
use App\Http\Resources\CountryResource;
use App\Http\Resources\PaymentResource;
use App\Models\Booking;
use App\Models\Card;
use App\Models\Country;
use App\Models\Payment;
use Illuminate\Http\Request;

class IndividualBookingPaymentController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Payment::class, 'payment');
    }

    public function index(Booking $individual_booking)
    {
        $payments = $individual_booking->payments()->with(['booking_client.client', 'card.address'])->oldest()->get();

        return PaymentResource::collection($payments)->additional([
            'booking' => new BookingResource($individual_booking->load(
                [
                    'clients.card',
                    'clients.client.cards'
                ]
            )),
            'countries' => CountryResource::collection(Country::with('states')->whereHas('states')->orderBy('name')->get()),
            'can' => [
                'create' => auth()->user()->can('create', Payment::class),
                'processPayments' => auth()->user()->can('processPayments', Payment::class),
            ]
        ]);
    }

    public function store(Booking $individual_booking, StorePayment $request)
    {
        $bookingClient = $individual_booking->clients()->find($request->input('client'));

        if ($request->input('useExistingCard', false)) {
            $card = Card::find($request->input('existingCard'));
        } else {
            $address = $bookingClient->client->addresses()->create([
                'country_id' => $request->input('address.country', 0) ? $request->input('address.country') : null,
                'other_country' => $request->input('address.country', 0) ? null : $request->input('address.otherCountry'),
                'state_id' => $request->input('address.state', 0) ? $request->input('address.state') : null,
                'other_state' => $request->input('address.state', 0) ? null : $request->input('address.otherState'),
                'city' => $request->input('address.city'),
                'line_1' => $request->input('address.line1'),
                'line_2' => $request->input('address.line2'),
                'zip_code' => $request->input('address.zipCode')
            ]);

            $card = $bookingClient->client->cards()->create([
                'name' => $request->input('card.name'),
                'type' => $request->input('card.type'),
                'number' => $request->input('card.number'),
                'expiration_date' => $request->input('card.expMonth') . $request->input('card.expYear'),
                'code' => $request->input('card.code'),
                'address_id' => $address->id
            ]);
        }

        $payment = $bookingClient->payments()->create([
            'card_id' => $card->id,
            'amount' => $request->input('amount'),
            'notes' => $request->input('notes')
        ]);

        return (new PaymentResource($payment))->response()->setStatusCode(201);
    }

    public function update(Booking $individual_booking, Payment $payment, UpdatePayment $request)
    {
        $payment->fill([
            'notes' => $request->input('notes'),
            'amount' => $request->input('amount')
        ])->save();

        return new PaymentResource($payment);
    }

    public function confirm(Booking $individual_booking, Payment $payment, Request $request)
    {
        $this->authorize('confirm', $payment);

        $payment->fill([
            'confirmed_at' => now()
        ])->save();

        event(new PaymentConfirmed($payment, $request->input('sendEmail', false)));

        $individual_booking->total <= $individual_booking->payment_total ? $individual_booking->is_paid = true : $individual_booking->is_paid = false;
        $individual_booking->save();

        return new PaymentResource($payment);
    }

    public function destroy(Booking $individual_booking, Payment $payment, DeletePayment $request)
    {
        $payment->fill([
            'cancelled_at' => now(),
            'card_declined' => $request->input('cardDeclined')
        ])->save();

        if ($request->input('cardDeclined')) {
            event(new CardDeclined($payment));
        }

        return new PaymentResource($payment);
    }

    public function forceDestroy(Booking $booking, Payment $payment)
    {
        $payment->delete();
    }
}
