<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Gets the authenticated user.
     *
     * @return \Illuminate\Http\Response
     */
    public function data(Request $request)
    {
        $user = auth()->user();

        return response()->json([
            'user' => [
                'firstName' => $user->first_name,
                'lastName' => $user->last_name,
                'username' => $user->username,
                'email' => $user->email,
            ],
            'dashboard' => [
                'logo' => '',
                'mainLogo' => asset('img/Mangata_Escapes.png'),
                'modules' => [
                    'dashboard' => [...array_filter([
                        'home',
                    ])],
                    'security' => [...array_filter([
                        $user->can('viewAny', \App\User::class) ? 'users' : '',
                        $user->can('viewAny', \Spatie\Permission\Models\Role::class) ? 'roles' : '',
                    ])],
                    'travel' => [...array_filter([
                        $user->can('manage transfers') ? 'transfers' : '',
                        $user->can('viewAny', \App\Models\Provider::class) ? 'providers' : '',
                        $user->can('viewAny', \App\Models\TravelAgent::class) ? 'agents' : '',
                        $user->can('viewAny', \App\Models\Destination::class) ? 'destinations' : '',
                        $user->can('viewAny', \App\Models\Hotel::class) ? 'hotels' : '',
                        $user->can('manage airports') ? 'airports' : '',
                        $user->can('manage airlines') ? 'airlines' : '',
                    ])],
                    'bookings' => [...array_filter([
                        $user->can('viewAny', \App\Models\Group::class) ? 'groups' : '',
                        $user->can('viewAny', \App\Models\Booking::class) ? 'individual-bookings' : '',
                        $user->can('viewAny', \App\Models\Group::class) ? 'deleted-groups' : '',
                        $user->can('viewAny', \App\Models\Booking::class) ? 'unpaid-bookings' : '',
                        $user->can('viewAny', \App\Models\Booking::class) ? 'pending' : '',
                        $user->can('manage calendar') ? 'calendar' : '',
                        $user->can('manage event types') ? 'event-types' : '',
                        $user->can('manage faqs') ? 'faqs' : '',
                        $user->can('manage notifications') ? 'notifications' : '',
                    ])],
                    'crm' => [...array_filter([
                        $user->can('viewAny', \App\Models\Lead::class) ? 'leads' : '',
                        $user->can('manage brands') ? 'brands' : '',
                    ])],
                ]
            ]
        ]);
    }
}
