<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\GetImages;
use App\Http\Requests\StoreRoom;
use App\Http\Requests\UpdateRoom;
use App\Http\Requests\UpdateRoomBeds;
use App\Http\Resources\GroupResource;
use App\Http\Resources\HotelResource;
use App\Http\Resources\RoomResource;
use App\Models\Group;
use App\Models\Hotel;
use App\Models\Room;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    use GetImages;

    public function __construct()
    {
        $this->authorizeResource(Room::class, 'room');
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Hotel  $hotel
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Hotel $hotel)
    {
        return RoomResource::collection($hotel->rooms()->orderBy('name')->get())->additional([
            'hotel' => new HotelResource($hotel),
            'can' => [
                'create' => auth()->user()->can('create', Room::class),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreRoom  $request
     * @param  \App\Models\Hotel  $hotel
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRoom $request, Hotel $hotel)
    {
        $roomImage = is_null($request->input('image')) ? null : $this->getImage($request->input('image'))->id;

        $room = $hotel->rooms()->create([
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'size' => $request->input('size'),
            'view' => $request->input('view'),
            'image_id' => $roomImage,
            'min_occupants' => $request->input('minOccupants'),
            'max_occupants' => $request->input('maxOccupants'),
            'adults_only' => $request->input('adultsOnly', false),
            'max_adults' => $request->input('adultsOnly', false) ? null : $request->input('maxAdults'),
            'max_children' => $request->input('adultsOnly', false) ? null : $request->input('maxChildren'),
            'min_adults_per_child' => $request->input('adultsOnly', false) ? null : $request->input('minAdultsPerChild'),
            'max_children_per_adult' => $request->input('adultsOnly', false) ? null : $request->input('maxChildrenPerAdult'),
        ]);

        return (new RoomResource($room))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Hotel  $hotel
     * @param  \App\Models\Room  $room
     * @return \Illuminate\Http\Response
     */
    public function show(Hotel $hotel, Room $room)
    {
        return (new RoomResource($room->load('image')))->additional([
            'hotel' => new HotelResource($hotel->load(['rooms' => function ($query) {
                $query->orderBy('name');
            }])),
            'activeGroups' => GroupResource::collection(Group::whereDate('event_date', '>', today())->whereHas('hotels.rooms', function ($query) use ($room) {
                $query->where('room_id', $room->id);
            })->get())
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateRoom  $request
     * @param  \App\Models\Hotel  $hotel
     * @param  \App\Models\Room  $room
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRoom $request, Hotel $hotel, Room $room)
    {
        if (is_array($request->input('image'))) {
            $room->image()->associate($this->getImage($request->input('image'))->id);
        } else {
            $room->image()->dissociate();
        }

        $room->update([
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'size' => $request->input('size'),
            'view' => $request->input('view'),
            'min_occupants' => $request->input('minOccupants'),
            'max_occupants' => $request->input('maxOccupants'),
            'adults_only' => $request->input('adultsOnly', false),
            'max_adults' => $request->input('adultsOnly', false) ? null : $request->input('maxAdults'),
            'max_children' => $request->input('adultsOnly', false) ? null : $request->input('maxChildren'),
            'min_adults_per_child' => $request->input('adultsOnly', false) ? null : $request->input('minAdultsPerChild'),
            'max_children_per_adult' => $request->input('adultsOnly', false) ? null : $request->input('maxChildrenPerAdult'),
        ]);

        return new RoomResource($room->load('image'));
    }

    /**
     * Update the specified resource's beds in storage.
     *
     * @param  \App\Http\Requests\UpdateRoomBeds  $request
     * @param  \App\Models\Hotel  $hotel
     * @param  \App\Models\Room  $room
     * @return \Illuminate\Http\Response
     */
    public function updateBeds(UpdateRoomBeds $request, Hotel $hotel, Room $room)
    {
        $room->beds = $request->input('beds');
        $room->save();

        return response()->json($room->beds);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Hotel  $hotel
     * @param  \App\Models\Room  $room
     * @return \Illuminate\Http\Response
     */
    public function destroy(Hotel $hotel, Room $room)
    {
        $room->delete();

        return response()->json()->setStatusCode(204);
    }
}
