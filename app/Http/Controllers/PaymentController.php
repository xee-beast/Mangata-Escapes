<?php

namespace App\Http\Controllers;

use App\Events\CardDeclined;
use App\Http\Requests\DeletePayment;
use App\Http\Requests\StorePayment;
use App\Http\Requests\UpdatePayment;
use App\Http\Resources\BookingResource;
use App\Http\Resources\CountryResource;
use App\Http\Resources\GroupResource;
use App\Http\Resources\PaymentResource;
use App\Models\Booking;
use App\Models\Card;
use App\Models\Country;
use App\Models\Group;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Payment::class, 'payment');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Group $group, Booking $booking)
    {
        $payments = $booking->payments()->with(['booking_client.client', 'card.address'])->oldest()->get();

        return PaymentResource::collection($payments)->additional([
            'booking' => new BookingResource($booking->load(
                [
                    'clients.card',
                    'clients.client.cards'
                ]
            )),
            'group' => new GroupResource($group),
            'countries' => CountryResource::collection(Country::with('states')->whereHas('states')->orderBy('name')->get()),
            'can' => [
                'create' => auth()->user()->can('create', Payment::class),
                'processPayments' => auth()->user()->can('processPayments', Payment::class),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Models\Group $Group
     * @param  \App\Models\Booking $booking
     * @param  \App\Http\Requests\StorePayment  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Group $group, Booking $booking, StorePayment $request)
    {
        $bookingClient = $booking->clients()->find($request->input('client'));

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

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Models\Group $Group
     * @param  \App\Models\Booking $booking
     * @param  \App\Models\Payment  $payment
     * @param  \App\Http\Requests\UpdatePayment  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Group $group, Booking $booking, Payment $payment, UpdatePayment $request)
    {
        $payment->fill([
            'notes' => $request->input('notes'),
            'amount' => $request->input('amount')
        ])->save();

        return new PaymentResource($payment);
    }

    /**
     * Confirm the payment.
     *
     * @param  \App\Models\Group $Group
     * @param  \App\Models\Booking $booking
     * @param  \App\Models\Payment  $payment
     * @param  \App\Http\Requests\UpdatePayment  $request
     * @return \Illuminate\Http\Response
     */
    public function confirm(Group $group, Booking $booking, Payment $payment, Request $request)
    {
        $this->authorize('confirm', $payment);

        $payment->fill([
            'confirmed_at' => now()
        ])->save();

        if ($group->is_active) {
            event(new \App\Events\PaymentConfirmed($payment, $request->input('sendEmail', false)));
        }

        $booking->total <= $booking->payment_total ? $booking->is_paid = true : $booking->is_paid = false;
        $booking->save();

        return new PaymentResource($payment);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Group $Group
     * @param  \App\Models\Booking $booking
     * @param  \App\Models\Payment  $payment
     * @param  \App\Http\Requests\DeletePayment  $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Group $group, Booking $booking, Payment $payment, DeletePayment $request)
    {
        $payment->fill([
            'cancelled_at' => now(),
            'card_declined' => $request->input('cardDeclined')
        ])->save();

        if ($group->is_active && $request->input('cardDeclined')) {
            event(new CardDeclined($payment));
        }


        return new PaymentResource($payment);
    }

    public function forceDestroy(Group $group, Booking $booking, Payment $payment)
    {
        $payment->delete();
    }
}
