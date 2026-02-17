<?php

namespace App\Http\Controllers\Couples;

use App\Events\CardUpdated;
use App\Models\Client;
use App\Models\Group;
use App\Http\Controllers\Controller;
use App\Http\Requests\Couples\UpdateCard;

class UpdateCardController extends Controller
{
    /**
     * Update the client's card on file.
     *
     * @param \App\Models\Group $group
     * @param int $step
     * @param \App\Http\Requests\Couples\UpdateCard $request
     * @return \Illuminate\Http\Response
     */
    public function updateCard(Group $group, $step, UpdateCard $request) 
    {
        $client = Client::where('email', $request->input('booking.email'))->first();
        $clientBooking = $client->bookings()->where('reservation_code', $request->input('booking.code'))->first();
        $mustSignInsurance = is_null($clientBooking->insurance);

        if ($clientBooking->booking->group->is_fit && is_null($clientBooking->acceptedFitQuote)) {
            return response()->json([
                'message' => 'A quote has not been agreed upon yet.'
            ], 403);
        }

        if ($step < 2) {
            return response()->json([
                'client' => [
                    'name' => $clientBooking->name
                ],
                'card' => !is_null($clientBooking->card)
                    ? [
                        'name' => $clientBooking->card->name,
                        'type' => $clientBooking->card->type,
                        'lastDigits' => $clientBooking->card->last_digits,
                        'expMonth' => $clientBooking->card->exp_month,
                        'expYear' => $clientBooking->card->exp_year,
                        'address' => [
                            'country' => $clientBooking->card->address->country_name,
                            'state' => $clientBooking->card->address->state_name,
                            'city' => $clientBooking->card->address->city,
                            'line1' => $clientBooking->card->address->line_1,
                            'line2' => $clientBooking->card->address->line_2,
                            'zipCode' => $clientBooking->card->address->zip_code
                        ]
                    ]
                    : [],
                    'mustSignInsurance' => $mustSignInsurance
            ]
                
            );
        }

        $card = $client->cards()->create([
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

        $clientBooking->card()->associate($card)->save();

        if ($mustSignInsurance) {
            $clientBooking->update([
                'insurance' => $request->input('insurance.accept'),
                'insurance_signed_at' => now()
            ]);

            $clientBooking->guests()->update([
                'insurance' => $request->input('insurance.accept')
            ]);
        }

        event(new CardUpdated($clientBooking, $mustSignInsurance));

        return response()->json();
    }
}
