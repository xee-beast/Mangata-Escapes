<?php

namespace App\Http\Controllers\Bookings;

use App\Events\PaymentSubmitted;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bookings\NewPayment;
use App\Models\Client;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function newPayment($step, NewPayment $request)
    {
        $client = Client::where('email', $request->input('booking.email'))->first();
        $clientBooking = $client->bookings()->where('reservation_code', $request->input('booking.code'))->first();
        $mustSignInsurance = is_null($clientBooking->insurance);

        if (is_null($clientBooking->acceptedFitQuote)) {
            return response()->json([
                'message' => 'A quote has not been agreed upon yet.'
            ], 403);
        }

        $TotalMinimumDeposit =  $clientBooking->booking->minimum_deposit;
        $clientMinimumPayment =  $clientBooking->booking->getMinimumPayment($clientBooking);
        $totalPayment = $clientBooking->booking->payments->where('cancelled_at', null)->sum('amount');
        $remainingMinimumDeposit = $TotalMinimumDeposit - $totalPayment;

        $clientTotalPayment = $clientBooking->payments->where('cancelled_at', null)->sum('amount');
        $clientRemainingMinimumDeposit = $clientMinimumPayment - $clientTotalPayment;
        
        $nextPA = $clientBooking->paymentArrangements()
            ->where('due_date', '>=', now()->startOfDay())
            ->orderBy('due_date', 'asc')
            ->first();

        if ($nextPA) {
            $totalMinimumPaymentRequired = ceil($nextPA->amount * 100) / 100;
        } else if ($remainingMinimumDeposit > 0 && $clientRemainingMinimumDeposit > 0) {
            $totalMinimumPaymentRequired = ceil(min($clientRemainingMinimumDeposit, $remainingMinimumDeposit) * 100) / 100;
        } else {
            $totalMinimumPaymentRequired = 0;
        }

        $insuranceRate = $clientBooking->fitRate->insurance ?? 0;

        if ($step < 2) {
            return response()->json([
                'client' => [
                    'name' => $clientBooking->name
                ],
                'booking' => [
                    'total' => $clientBooking->booking->getClientTotal($clientBooking),
                    'payments' => $clientBooking->booking->getPaymentTotal($clientBooking),
                    'requiredPaymentDeposit' => $totalMinimumPaymentRequired > 0 ? true : false,
                    'minimumPayment' => $totalMinimumPaymentRequired,
                    'cancellationDate' => $clientBooking->booking->cancellation_date ? $clientBooking->booking->cancellation_date->format('m/d/Y') : 'the cancellation date',
                    'id' => $clientBooking->booking->id,
                    'balance_due_date' => $clientBooking->booking->balance_due_date,
                    'booking_due_dates' => $clientBooking->booking->bookingDueDates,
                    'insuranceRate' => ceil($insuranceRate * 100) / 100,
                ],
                'card' => !is_null($clientBooking->card)
                ? [
                    'name' => $clientBooking->card->name,
                    'type' => $clientBooking->card->type,
                    'lastDigits' => $clientBooking->card->last_digits
                ]
                : [],
                'mustSignInsurance' => $mustSignInsurance,
                'supplierName' => $clientBooking->booking->provider ? $clientBooking->booking->provider->name : '',
            ]);
        }

        if ($request->input('insurance.accept')) {
            $totalMinimumPaymentRequired = $totalMinimumPaymentRequired + $insuranceRate;
        }

        if ((ceil($totalPayment * 100) / 100 < ceil($TotalMinimumDeposit * 100) / 100) && ((float) $request->input('amount') < $totalMinimumPaymentRequired)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['amount' => 'The minimum payment amount is $' . $totalMinimumPaymentRequired . '.']);
        }

        $card = $request->input('useCardOnFile') ? $clientBooking->card : $client->cards()->create([
            'name' => $request->input('card.name'),
            'type' => $request->input('card.type'),
            'number' => $request->input('card.number'),
            'expiration_date' => $request->input('card.expMonth') . $request->input('card.expYear'),
            'code' => $request->input('card.code'),
            'address_id' => $client->addresses()->create([
                'country_id' => $request->input('address.country') ? $request->input('address.country') : null,
                'other_country' => $request->input('address.country') ? null : $request->input('address.otherCountry'),
                'state_id' => $request->input('address.country') ? $request->input('address.state') : null,
                'other_state' => $request->input('address.country') ? null : $request->input('address.otherState'),
                'city' => $request->input('address.city'),
                'line_1' => $request->input('address.line1'),
                'line_2' => $request->input('address.line2'),
                'zip_code' => $request->input('address.zipCode')
            ])->id
        ]);

        if (!$clientBooking->card()->exists() || $request->input('updateCardOnFile')) {
            $clientBooking->card()->associate($card)->save();
        }

        if ($mustSignInsurance) {
            $clientBooking->update([
                'insurance' => $request->input('insurance.accept'),
                'insurance_signed_at' => now()
            ]);

            $clientBooking->guests()->update([
                'insurance' => $request->input('insurance.accept')
            ]);
        }

        $payment = $clientBooking->payments()->create([
            'amount' => $request->input('amount'),
            'card_id' => $card->id
        ]);

        event(new PaymentSubmitted(
            $payment,
            $mustSignInsurance,
            $request->input('type', 'Payment towards balance')
        ));

        return response()->json();
    }
}
