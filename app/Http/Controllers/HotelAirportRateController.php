<?php

namespace App\Http\Controllers;

use App\Http\Resources\HotelAirportRateResource;
use App\Models\Hotel;
use App\Models\HotelAirportRate;
use Illuminate\Http\Request;

class HotelAirportRateController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Hotel  $hotel
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Hotel $hotel)
    {
        $request->validate([
            'airport_id' => 'required|exists:airports,id',
            'transportation_rate' => 'required|numeric|min:0',
            'single_transportation_rate' => 'required|numeric|min:0',
            'one_way_transportation_rate' => 'required|numeric|min:0',
        ]);

        $airportExists = $hotel->destination->airports()->where('airports.id', $request->input('airport_id'))->exists();
        
        if (!$airportExists) {
            return response()->json([
                'message' => 'The selected airport does not belong to this hotel\'s destination.'
            ], 422);
        }

        $existingRate = $hotel->hotelAirportRates()->where('airport_id', $request->input('airport_id'))->first();
        
        if ($existingRate) {
            return response()->json([
                'message' => 'A rate for this airport already exists.'
            ], 422);
        }

        $hotelAirportRate = new HotelAirportRate();
        $hotelAirportRate->hotel_id = $hotel->id;
        $hotelAirportRate->airport_id = $request->input('airport_id');
        $hotelAirportRate->transportation_rate = $request->input('transportation_rate');
        $hotelAirportRate->single_transportation_rate = $request->input('single_transportation_rate');
        $hotelAirportRate->one_way_transportation_rate = $request->input('one_way_transportation_rate');
        $hotelAirportRate->save();

        return (new HotelAirportRateResource($hotelAirportRate->load('airport')))->response()->setStatusCode(201);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Hotel  $hotel
     * @param  \App\Models\HotelAirportRate  $hotelAirportRate
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Hotel $hotel, HotelAirportRate $hotelAirportRate)
    {
        $request->validate([
            'transportation_rate' => 'required|numeric|min:0',
            'single_transportation_rate' => 'required|numeric|min:0',
            'one_way_transportation_rate' => 'required|numeric|min:0',
        ]);

        $hotelAirportRate->transportation_rate = $request->input('transportation_rate');
        $hotelAirportRate->single_transportation_rate = $request->input('single_transportation_rate');
        $hotelAirportRate->one_way_transportation_rate = $request->input('one_way_transportation_rate');
        $hotelAirportRate->save();

        return new HotelAirportRateResource($hotelAirportRate->load('airport'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Hotel  $hotel
     * @param  \App\Models\HotelAirportRate  $hotelAirportRate
     * @return \Illuminate\Http\Response
     */
    public function destroy(Hotel $hotel, HotelAirportRate $hotelAirportRate)
    {
        $hotelAirportRate->delete();

        return response()->json()->setStatusCode(204);
    }
}
