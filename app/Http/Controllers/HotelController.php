<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\GetImages;
use App\Http\Requests\StoreHotel;
use App\Http\Requests\UpdateHotel;
use App\Http\Requests\UpdateHotelImages;
use App\Http\Resources\HotelResource;
use App\Models\Destination;
use App\Models\Hotel;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HotelController extends Controller
{
    use GetImages;

    public function __construct()
    {
        $this->authorizeResource(Hotel::class, 'hotel');
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $hotels = Hotel::withTrashed()->with(['destination.country'])->orderBy('name');

        $destination = $request->query('destination', '');
        if (!empty($destination)) {
            $hotels->where('destination_id', $destination);
        }

        $search = $request->query('search', '');
        if (!empty($search)) {
            $hotels->where('name', 'like', '%' . $search . '%');
        }

        return HotelResource::collection($hotels->paginate($request->query('paginate', 25)))
                ->additional([
                    'destinations' => Destination::select(
                        'destinations.id AS value',
                        DB::raw("CONCAT(destinations.name, ', ', countries.name) AS text"))->join('countries', 'destinations.country_id', '=', 'countries.id'
                    )->get(),
                    'can' => [
                        'create' => $request->user()->can('create', Hotel::class),
                    ],
                ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreHotel  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreHotel $request)
    {
        $hotel = new Hotel;
        $hotel->name = $request->input('name');
        $hotel->destination_id = $request->input('destination');
        $hotel->description = $request->input('description', '');
        $hotel->url = $request->input('url');

        if (is_array($request->input('travelDocsCoverImage'))) {
            $hotel->travel_docs_cover_image()->associate($this->getImage($request->input('travelDocsCoverImage'))->id);
        }

        if (is_array($request->input('travelDocsImageTwo'))) {
            $hotel->travel_docs_image_two()->associate($this->getImage($request->input('travelDocsImageTwo'))->id);
        }

        if (is_array($request->input('travelDocsImageThree'))) {
            $hotel->travel_docs_image_three()->associate($this->getImage($request->input('travelDocsImageThree'))->id);
        }

        $hotel->save();

        $hotel->images()->sync($this->getImages($request->input('images') ?? [])->pluck('id'));

        return (new HotelResource($hotel))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Hotel  $hotel
     * @return \Illuminate\Http\Response
     */
    public function show(Hotel $hotel)
    {
        return (new HotelResource($hotel->load(['destination.country', 'destination.airports', 'travel_docs_cover_image', 'travel_docs_image_two', 'travel_docs_image_three', 'images', 'hotelAirportRates.airport'])))->additional([
            'meta' => [
                'destinations' => Destination::select('destinations.id AS value', DB::raw("CONCAT(destinations.name, ', ', countries.name) AS text"))
                                    ->join('countries', 'destinations.country_id', '=', 'countries.id')->get(),
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Hotel  $hotel
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateHotel $request, Hotel $hotel)
    {
        $hotel->name = $request->input('name');
        $hotel->description = $request->input('description');
        $hotel->destination_id = $request->input('destination', '');
        $hotel->url = $request->input('url');

        if (is_array($request->input('travelDocsCoverImage'))) {
            $hotel->travel_docs_cover_image()->associate($this->getImage($request->input('travelDocsCoverImage'))->id);
        } else {
            $hotel->travel_docs_cover_image()->dissociate();
        }

        if (is_array($request->input('travelDocsImageTwo'))) {
            $hotel->travel_docs_image_two()->associate($this->getImage($request->input('travelDocsImageTwo'))->id);
        } else {
            $hotel->travel_docs_image_two()->dissociate();
        }

        if (is_array($request->input('travelDocsImageThree'))) {
            $hotel->travel_docs_image_three()->associate($this->getImage($request->input('travelDocsImageThree'))->id);
        } else {
            $hotel->travel_docs_image_three()->dissociate();
        }

        $hotel->save();

        return new HotelResource($hotel->loadMissing(['destination.country', 'travel_docs_cover_image', 'travel_docs_image_two', 'travel_docs_image_three', 'images']));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Hotel  $hotel
     * @return \Illuminate\Http\Response
     */
    public function destroy(Hotel $hotel)
    {
        $hotel->delete();

        return response()->json()->setStatusCode(204);
    }

    /**
     * Sync the hotels images.
     *
     * @param  \App\Models\Hotel  $hotel
     * @param  \App\Http\Requests\SyncImages  $request
     * @return \Illuminate\Http\Response
     */
    public function syncHotelImages(Hotel $hotel, UpdateHotelImages $request) {
        $oldImageIds = $hotel->images->pluck('id');
        $hotel->images()->sync($this->getImages($request->input('images') ?? collect())->pluck('id'));
        $removedImageIds = $oldImageIds->diff($hotel->images->pluck('id'));
        Image::whereIn('id', $removedImageIds)->get()->each->delete();

        return response()->json()->setStatusCode(204);
    }

    /**
     * Enable the specified resource from storage.
     *
     * @param  \App\Models\Hotel  $hotel
     * @return \Illuminate\Http\Response
     */
    public function enable(Hotel $hotel)
    {
        $hotel->restore();

        return response()->json()->setStatusCode(204);
    }
}
