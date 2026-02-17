<?php

namespace App\Http\Controllers\Couples;

use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BookingDetailController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8|max:25',
            'group_id' => 'required',
        ]);

        $group = Group::where('id', $request->group_id)->where('email', $request->email)->first();

        if (!$group) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.']
            ]);
        } else {
            if ($group->password !== $request->password) {
                throw ValidationException::withMessages([
                    'password' => ['The provided credentials are incorrect.']
                ]);
            }
        }

        $bookings = $group->bookings()->withTrashed()->ordered()
            ->with([
                'clients.guests',
                'roomBlocks.room.hotel',
                'trackedChanges' => function ($query) {
                    $query->whereNull('confirmed_at');
                }
            ])
            ->whereHas('roomBlocks', function ($query) {
                $query->where('is_active', true);
            })
            ->get();

        return response()->json([
            'group' => $group,
            'bookings' => $bookings,
        ]);
    }
}
