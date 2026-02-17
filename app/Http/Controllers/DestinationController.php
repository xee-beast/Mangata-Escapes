<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDestination;
use App\Http\Requests\UpdateDestination;
use App\Http\Resources\CountryResource;
use App\Http\Resources\DestinationResource;
use App\Http\Controllers\Traits\GetImages;
use App\Models\Country;
use App\Models\Destination;
use App\Models\Airport;
use Illuminate\Http\Request;
use App\Http\Requests\AddAirport;

class DestinationController extends Controller
{
    use GetImages;

    public function __construct()
    {
        $this->authorizeResource(Destination::class, 'destination');
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $destinations = Destination::query()->with('country');

        $country = $request->query('country', '');
        if(!empty($country)) {
            $destinations->whereHas('country', function ($query) use ($country) {
                $query->where('id', $country);
            });
        }

        $search = $request->query('search', '');
        if (!empty($search)) {
            $destinations->where('name', 'like', '%' . $search . '%');
        }

        $destinations->orderBy('country_id', 'asc')->orderBy('name', 'asc');

        return DestinationResource::collection($destinations->with(['airports'])->paginate($request->query('paginate', 10)))
            ->additional([
                'airports' => Airport::all(),
                'countries' => CountryResource::collection(Country::whereHas('destinations')->get()),
                'can' => [
                    'create' => $request->user()->can('create', Destination::class),
                ],
            ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreDestination  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreDestination $request)
    {
        $destination = new Destination;
        $destination->name = $request->input('name');
        $destination->weather_description = $request->input('weatherDescription');
        $destination->outlet_adapter = $request->input('outletAdapter', false);
        $destination->tax_description = $request->input('taxDescription');
        $destination->language_description = $request->input('languageDescription');
        $destination->currency_description = $request->input('currencyDescription');
        
        if (is_array($request->input('image'))) {
            $destination->image()->associate($this->getImage($request->input('image'))->id);
        }

        if($request->input('country')) {
            $country = Country::find($request->input('country'));
        } else {
            $country = Country::firstOrCreate(['name' => $request->input('otherCountry')]);
        }
        $destination->country()->associate($country);

        $destination->save();

        $airportCodes = collect($request->input('airports', []))->pluck('airport_code')->toArray();
        $airports = Airport::whereIn('airport_code', $airportCodes)->pluck('id')->toArray();
        $destination->airports()->attach($airports);

        return (new DestinationResource($destination))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Destination  $destination
     * @return \Illuminate\Http\Response
     */
    public function show(Destination $destination)
    {
        return (new DestinationResource($destination->loadMissing(['country', 'airports' , 'image'])))
            ->additional([
                'airports' => Airport::all(),
                'can' => [
                    'viewHotels' => auth()->user()->can('viewAny', \App\Models\Hotel::class)
                ]
            ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateDestination  $request
     * @param  \App\Models\Destination  $destination
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateDestination $request, Destination $destination)
    {
        $destination->name = $request->input('name');
        $destination->weather_description = $request->input('weatherDescription');
        $destination->outlet_adapter = $request->input('outletAdapter', false);
        $destination->tax_description = $request->input('taxDescription');
        $destination->language_description = $request->input('languageDescription');
        $destination->currency_description = $request->input('currencyDescription');

        if (is_array($request->input('image'))) {
            $destination->image()->associate($this->getImage($request->input('image'))->id);
        } else {
            $destination->image()->dissociate();
        }

        $destination->save();

        $airportCodes = collect($request->input('airports', []))->pluck('airport_code')->toArray();
        $airports = Airport::whereIn('airport_code', $airportCodes)->pluck('id')->toArray();
        $destination->airports()->sync($airports);

        return new DestinationResource($destination->loadMissing(['country', 'image']));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Destination  $destination
     * @return \Illuminate\Http\Response
     */
    public function destroy(Destination $destination)
    {
        $destination->airports()->detach();
        
        $destination->delete();

        return response()->json()->setStatusCode(204);
    }

    public function validateAirport(AddAirport $request) 
    {
        return response()->json();
    }
}
