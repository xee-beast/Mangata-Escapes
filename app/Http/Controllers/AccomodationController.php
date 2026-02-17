<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAccomodation;
use App\Http\Requests\UpdateAccomodation;
use App\Http\Requests\UpdateRates;
use App\Http\Resources\GroupResource;
use App\Http\Resources\HotelBlockResource;
use App\Http\Resources\HotelResource;
use App\Http\Resources\RoomBlockResource;
use App\Models\Group;
use App\Models\HotelBlock;
use App\Models\RoomBlock;
use Illuminate\Validation\ValidationException;

class AccomodationController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(RoomBlock::class, 'roomBlock');
    }

    /**
     * Display a listing of the resource.
     *
     * @param \App\Models\Group $group
     * @return \Illuminate\Http\Response
     */
    public function index(Group $group)
    {
        $accomodations = $group->hotels()->with(['rooms.rates', 'rooms.child_rates'])->get();

        return HotelBlockResource::collection($accomodations)->additional([
            'hotels' => HotelResource::collection($group->destination->hotels()->with('rooms')->get()),
            'group' => new GroupResource($group),
            'can' => [
                'create' => auth()->user()->can('create', [RoomBlock::class, $group])
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \App\Models\Group $group
     * @param  \App\Http\Requests\StoreAccomodation  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Group $group, StoreAccomodation $request)
    {
        $hotelBlock = $group->hotels()->firstOrCreate([
            'hotel_id' => $request->input('hotel')
        ]);

        $room = $hotelBlock->hotel->rooms()->find($request->input('room'));

        $roomBlock = $hotelBlock->rooms()->firstOrCreate(
            ['room_id' => $room->id],
            [
                'min_adults_per_child' => $request->input('minAdultsPerChild', $room->min_adults_per_child ?? 1),
                'max_children_per_adult' => $request->input('maxChildrenPerAdult', $room->max_children_per_adult ?? 1),
                'start_date' => $request->input('dates.start'),
                'end_date' => $request->input('dates.end'),
                'split_date'=> $request->input('hasSplitDates', false) ? $request->input('splitDate') : null,
                'inventory' => $request->input('inventory')
            ]
        );

        if (!$roomBlock->wasRecentlyCreated) {
            throw ValidationException::withMessages(['room' => 'The room is already blocked for this group.']);
        }

        if (!$group->is_fit) {
            $roomBlock->syncRates($request->input('rates'));

            if (!$roomBlock->room->adults_only) {
                $roomBlock->syncChildRates($request->input('childRates', []));
            }
        }

        return (new HotelBlockResource($hotelBlock))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Group $group
     * @param  \App\Models\RoomBlock  $roomBlock
     * @return \Illuminate\Http\Response
     */
    public function show(Group $group, RoomBlock $roomBlock)
    {
        return (new RoomBlockResource($roomBlock->load('rates', 'child_rates')))->additional([
            'group' => new GroupResource($group),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \App\Models\Group $group
     * @param  \App\Models\RoomBlock  $roomBlock
     * @param  \App\Http\Requests\UpdateAccomodation  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Group $group, RoomBlock $roomBlock, UpdateAccomodation $request)
    {
        $roomBlock->inventory = $request->input('inventory');
        $roomBlock->sold_out = $request->input('soldOut');
        $roomBlock->is_visible = $request->input('isVisible', true);
        $roomBlock->start_date = $request->input('dates.start');
        $roomBlock->end_date = $request->input('dates.end');
        $roomBlock->save();

        foreach ($group->bookings as $booking) {
            $booking->total <= $booking->payment_total ? $booking->is_paid = true : $booking->is_paid = false;
            $booking->save();
        }

        return new RoomBlockResource($roomBlock);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Group $group
     * @param  \App\Models\RoomBlock  $roomBlock
     * @return \Illuminate\Http\Response
     */
    public function destroy(Group $group, RoomBlock $roomBlock)
    {
        $hasBooking = $roomBlock->bookings()->withTrashed()->exists();

        if ($hasBooking) {
            return response()->json([
                'message' => 'This room cannot be deleted because it is linked to a booking.'
            ], 422);
        }

        $hotelBlock = $roomBlock->hotel_block;
        $roomBlock->delete();

        if ($hotelBlock->rooms()->count() === 0) {
            $hotelBlock->delete();
        }

        return response()->json()->setStatusCode(204);
    }

    /**
     * Define rates for selected roomBlock.
     *
     * @param \App\Models\Group $group
     * @param  \App\Models\RoomBlock  $roomBlock
     * @param  \App\Http\Requests\SyncRates  $request
     * @return \Illuminate\Http\Response
     */
    public function updateRates(Group $group, RoomBlock $roomBlock, UpdateRates $request)
    {
        $roomBlock->split_date = $request->input('hasSplitDates', false) ? $request->input('splitDate') : null;
        $roomBlock->min_adults_per_child = $request->input('minAdultsPerChild', $roomBlock->room->min_adults_per_child);
        $roomBlock->max_children_per_adult = $request->input('maxChildrenPerAdult', $roomBlock->room->max_children_per_adult);
        $roomBlock->save();

        if (!$group->is_fit) {
            $roomBlock->syncRates($request->input('rates'));
            $roomBlock->syncChildRates($request->input('childRates', []));
        }

        foreach ($group->bookings as $booking) {
            $booking->total <= $booking->payment_total ? $booking->is_paid = true : $booking->is_paid = false;
            $booking->save();
        }

        return (new RoomBlockResource($roomBlock));
    }

    public function roomtoggleActive(Group $group, RoomBlock $roomBlock)
    {
        if ($roomBlock->is_active) {
            $hasBookings = $roomBlock->bookings()->whereNull('deleted_at')->exists();

            if ($hasBookings) {
                return response()->json([
                    'is_active' => true,
                    'message' => 'Room cannot be deactivated because it has active bookings.',
                ], 422);
            }
        }

        $roomBlock->is_active = !$roomBlock->is_active;
        $roomBlock->save();

        return response()->json([
            'is_active' => $roomBlock->is_active,
        ]);
    }

    public function hotelToggleActive(Group $group, HotelBlock $hotelBlock)
    {
        $hasBooking = $hotelBlock->rooms()->whereHas('bookings', function ($query) {
            $query->whereNull('deleted_at');
        })->exists();

        if ($hasBooking) {
            return response()->json([
                'is_active' => true,
                'message' => 'Hotel cannot be deactivated because it has active bookings.',
            ], 422);
        }

        $shouldActivate = !$hotelBlock->rooms()->where('is_active', true)->exists();
        $hotelBlock->rooms()->update(['is_active' => $shouldActivate]);

        return response()->json([
            'is_active' => $shouldActivate,
        ]);
    }

    /**
     * Toggle the visibility of a room block
     *
     * @param  \App\Models\Group  $group
     * @param  \App\Models\RoomBlock  $roomBlock
     * @return \Illuminate\Http\Response
     */
    public function toggleVisibility(Group $group, RoomBlock $roomBlock)
    {
        $this->authorize('update', $roomBlock);
        
        $roomBlock->update([
            'is_visible' => !$roomBlock->is_visible
        ]);

        return response()->json([
            'is_visible' => $roomBlock->is_visible,
            'message' => $roomBlock->is_visible ? 'Accommodation is now visible to couples.' : 'Accommodation is now hidden from couples.'
        ]);
    }
}
