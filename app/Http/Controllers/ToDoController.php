<?php

namespace App\Http\Controllers;

use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Provider;
use App\Models\TravelAgent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ToDoController extends Controller
{
    /**
     * Display a listing of pending bookings & payments.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function pending(Request $request)
    {
        $bookings = Booking::query()->with(['travel_agent', 'provider', 'group.travel_agent', 'group.provider', 'payments', 'trackedChanges', 'guestChanges',  'clients.pendingFitQuote', 'clients.acceptedFitQuote', 'clients.discardedFitQuote']);

        $bookings->where(function ($query) use ($request) {
            $query->whereHas('group', function ($q) use ($request) {
                    $q->whereDate('event_date', '>', Carbon::now());

                    if ($request->query('testGroups', 'false') == 'true') {
                        $q->whereNotIn('id', [273, 598]);
                    }
                })
                ->orWhereDate('check_in', '>', Carbon::now());
        });

        switch ($request->query('only')) {
            case 'bookings':
                $bookings->whereNull('confirmed_at');
                break;

            case 'payments':
                $bookings->whereHas('payments', function($query) {
                    $query->whereNull('confirmed_at')->whereNull('cancelled_at');
                });
                break;

            case 'changes':
                $bookings->where(function($query) {
                    $query->whereRelation('trackedChanges', 'confirmed_at', null);
                });
                break;

            case 'guestChanges':
                $bookings->whereHas('guestChanges', function($query) {
                    $query->whereNull('confirmed_at');
                });
                break;

            default:
                $bookings->where(function($query) {
                    $query->whereNull('confirmed_at')
                        ->orWhereRelation('trackedChanges', 'confirmed_at', null)
                        ->orWhereRelation('guestChanges', 'confirmed_at', null)
                        ->orWhereHas('payments', function($query) {
                            $query->whereNull('confirmed_at')->whereNull('cancelled_at');
                        });
                });
        }

        $provider = $request->query('provider', '');
        if (!empty($provider)) {
            $bookings->where(function ($query) use ($provider) {
                $query->whereHas('group', function ($q) use ($provider) {
                        $q->where('provider_id', $provider);
                    })
                    ->orWhere('provider_id', $provider);
            });
        }

        $agent = $request->query('agent', '');
        if (!empty($agent)) {
            $bookings->where(function ($query) use ($agent) {
                $query->whereHas('group', function ($q) use ($agent) {
                        $q->where('travel_agent_id', $agent);
                    })
                    ->orWhere('travel_agent_id', $agent);
            });
        }

        $search = $request->query('search', '');
        if (!empty($search)) {
            $bookings->where(function ($query) use ($search) {
                $query->whereHas('group', function ($q) use ($search) {
                        $q->where(DB::raw("CONCAT(bride_first_name, ' ', bride_last_name, ' ', groom_first_name, ' ', groom_last_name)"), 'LIKE', '%' . $search . '%')
                        ->orWhereRaw("CONCAT(bride_last_name, ' & ', groom_last_name) LIKE ?", [$search ])
                        ->orWhereRaw("CONCAT(bride_last_name, ' ', groom_last_name) LIKE ?", [$search]);
                    })
                    ->orWhere(DB::raw("CONCAT(reservation_leader_first_name, ' ', reservation_leader_last_name)"), 'LIKE', '%' . $search . '%');
            });
        }

        $bookings->withMax('payments', 'created_at')
            ->withMax(['trackedChanges' => fn($q) => $q->whereNull('confirmed_at')], 'created_at')
            ->withMax(['guestChanges' => fn($q) => $q->whereNull('confirmed_at')->whereNull('deleted_at')], 'created_at');

        // Apply sorting
        $sort = $request->query('sort', 'activity');
        if ($sort === 'alphabetical') {
            $bookings->leftJoin('groups', 'bookings.group_id', '=', 'groups.id')
                ->orderByRaw('
                    CASE
                        WHEN bookings.group_id IS NOT NULL
                        THEN LOWER(CONCAT_WS(" & ", groups.bride_last_name, groups.groom_last_name))
                        ELSE LOWER(CONCAT(bookings.reservation_leader_first_name, " ", bookings.reservation_leader_last_name))
                    END ASC
                ')
                ->select('bookings.*');
        } else {
            // Default sorting by most recent activity
            $bookings->orderByRaw("
                CASE
                    WHEN payments_max_created_at IS NOT NULL THEN payments_max_created_at
                    WHEN tracked_changes_max_created_at IS NOT NULL THEN tracked_changes_max_created_at
                    WHEN guest_changes_max_created_at IS NOT NULL THEN guest_changes_max_created_at
                    ELSE bookings.created_at
                END DESC
            ");
        }

        $bookings = $bookings->paginate($request->query('paginate', 10));

        return BookingResource::collection($bookings)
            ->additional([
                'agents' => TravelAgent::select(
                    'travel_agents.id AS value',
                    DB::raw("CONCAT(travel_agents.first_name, ' ', travel_agents.last_name) AS text")
                )->get(),
                'providers' => Provider::select(
                    'providers.id AS value',
                    'providers.name AS text'
                )->get()
            ]);
    }
}
