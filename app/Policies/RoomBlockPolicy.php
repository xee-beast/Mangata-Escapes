<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\RoomBlock;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RoomBlockPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any room blocks.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if (!is_null($group = request()->route('group'))) {
            return $user->can('view', $group);
        }

        return true;
    }

    /**
     * Determine whether the user can view the room block.
     *
     * @param  \App\User  $user
     * @param  \App\Models\RoomBlock  $roomBlock
     * @return mixed
     */
    public function view(User $user, RoomBlock $roomBlock)
    {
        return $user->can('view', $roomBlock->hotel_block->group);
    }

    /**
     * Determine whether the user can create room blocks.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        if (!is_null($group = request()->route('group'))) {
            return $user->can('update', $group);
        }

        return false;
    }

    /**
     * Determine whether the user can update the room block.
     *
     * @param  \App\User  $user
     * @param  \App\Models\RoomBlock  $roomBlock
     * @return mixed
     */
    public function update(User $user, RoomBlock $roomBlock)
    {
        return $user->can('update', $roomBlock->hotel_block->group);
    }

    /**
     * Determine whether the user can delete the room block.
     *
     * @param  \App\User  $user
     * @param  \App\Models\RoomBlock  $roomBlock
     * @return mixed
     */
    public function delete(User $user, RoomBlock $roomBlock)
    {
        return !$roomBlock->bookings()->exists() && $user->can('update', $roomBlock->hotel_block->group);
    }
}
