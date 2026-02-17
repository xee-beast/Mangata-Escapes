<?php

namespace App\Policies;

use App\Models\Hotel;
use App\Models\Room;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RoomPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any rooms.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if (!is_null($hotel = request()->route('hotel'))) {
            return $user->can('view', $hotel);
        }

        return true;
    }

    /**
     * Determine whether the user can view the room.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Room  $room
     * @return mixed
     */
    public function view(User $user, Room $room)
    {
        return $user->can('view', $room->hotel);
        
    }

    /**
     * Determine whether the user can create rooms.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        if (!is_null($hotel = request()->route('hotel'))) {
            return $user->can('update', $hotel);
        }

        return false;
    }

    /**
     * Determine whether the user can update the room.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Room  $room
     * @return mixed
     */
    public function update(User $user, Room $room)
    {
        return $user->can('update', $room->hotel);
    }

    /**
     * Determine whether the user can delete the room.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Room  $room
     * @return mixed
     */
    public function delete(User $user, Room $room)
    {
        return !$room->room_blocks()->exists() && $user->can('update', $room->hotel);
    }
}
