<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIndividualBooking extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'hotelAssistance' => 'required|boolean',
            'hotelPreferences' => 'required_if:hotelAssistance,true|nullable|string|max:5000',
            'hotelName' => 'required_if:hotelAssistance,false|nullable|string|max:255',
            'roomCategory' => 'required|boolean',
            'roomCategoryName' => 'required_if:roomCategory,true|nullable|string|max:255',
            'dates.start' => 'required|date|before:dates.end',
            'dates.end' => 'required|date|after:dates.start',
            'budget' => 'nullable|numeric|min:0',
            'specialRequests' => 'nullable|string|max:5000',
            'notes' => 'nullable|string|max:5000',
            'transportation' => 'required|boolean',
            'departureGateway' => 'nullable|string|max:255',
            'flightPreferences' => 'nullable|string|max:5000',
            'airlineMembershipNumber' => 'nullable|string|max:255',
            'knownTravelerNumber' => 'nullable|string|max:255',
            'flightMessage' => 'nullable|string|max:255',
            'transportationType' => 'nullable|in:private,shared',
            'transportationSubmitBefore' => 'nullable|date|before:dates.start',
            'transfer' => 'nullable|exists:transfers,id',
            'destination' => 'nullable|exists:destinations,id',
            'email' => 'required|email|max:255',
            'reservationLeaderFirstName' => 'required|string|max:255',
            'reservationLeaderLastName' => 'required|string|max:255',
            'deposit' => 'required|numeric|min:0',
            'depositType' => 'required|in:fixed,percentage',
            'agent' => 'required|exists:travel_agents,id',
            'provider' => 'required|exists:providers,id',
            'providerId' => 'nullable|string|max:255',
            'changeFeeDate' => 'nullable|date|before:dates.start',
            'changeFeeAmount' => 'nullable|numeric|min:0',
            'staffMessage' => 'nullable|string|max:5000',
            'travelDocsCoverImage' => 'nullable|array',
            'travelDocsCoverImage.uuid' => 'required_with:travelDocsCoverImage|uuid',
            'travelDocsCoverImage.path' => 'required_with:travelDocsCoverImage|string',
            'travelDocsImageTwo' => 'nullable|array',
            'travelDocsImageTwo.uuid' => 'required_with:travelDocsImageTwo|uuid',
            'travelDocsImageTwo.path' => 'required_with:travelDocsImageTwo|string',
            'travelDocsImageThree' => 'nullable|array',
            'travelDocsImageThree.uuid' => 'required_with:travelDocsImageThree|uuid',
            'travelDocsImageThree.path' => 'required_with:travelDocsImageThree|string',
            'bookingId' => 'required|string|max:50',
        ];
    }

    public function attributes()
    {
        return [
            'dates.start' => 'travel dates',
            'dates.end' => 'travel dates',
        ];
    }
}
