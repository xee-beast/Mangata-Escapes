<?php

namespace App\Http\Controllers\Couples;

use App\Events\PaymentSubmitted;
use App\Models\Client;
use App\Models\Group;
use App\Http\Controllers\Controller;
use App\Http\Requests\Couples\NewPayment;

class PaymentController extends Controller
{
    /**
     * Update the client's card on file.
     *
     * @param \App\Models\Group $group
     * @param int $step
     * @param \App\Http\Requests\Couples\NewPayment $request
     * @return \Illuminate\Http\Response
     */
    public function newPayment(Group $group, $step, NewPayment $request)
    {
        $client = Client::where('email', $request->input('booking.email'))->first();
        $clientBooking = $client->bookings()->where('reservation_code', $request->input('booking.code'))->first();
        $mustSignInsurance = is_null($clientBooking->insurance);

        if ($clientBooking->booking->group->is_fit && is_null($clientBooking->acceptedFitQuote)) {
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

        if ($clientBooking->booking->group && $clientBooking->booking->group->is_fit) {
            $insuranceRate = $clientBooking->fitRate->insurance ?? 0;
        } else {
            $insuranceRate = $clientBooking->booking->getInsuranceRates($clientBooking->guests(), true)->reduce(function ($insuranceTotal, $insurance) {
                return $insuranceTotal + ($insurance->rate * $insurance->quantity);
            }, 0);
        }

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
                    'insuranceRate' => ceil($insuranceRate * 100) / 100,
                ],
                'card' => !is_null($clientBooking->card)
                ? [
                    'name' => $clientBooking->card->name,
                    'type' => $clientBooking->card->type,
                    'lastDigits' => $clientBooking->card->last_digits
                ]
                : [],
                'mustSignInsurance' => $mustSignInsurance
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
