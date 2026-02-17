<?php

namespace App\Http\Controllers;

use App\Http\Resources\GroupResource;
use App\Models\Group;
use App\Models\TravelAgent;
use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrashController extends Controller
{
    /**
     * Display a listing of deleted groups.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $groups = Group::onlyTrashed()->with(['destination', 'travel_agent', 'provider', 'hotels']);

        if (!$request->query('old', false)) {
            $groups->whereDate('event_date', '>', now());
        }

        $agent = $request->query('agent', '');
        if(!empty($agent)) {
            $groups->where('travel_agent_id', $agent);
        }

        $provider = $request->query('provider', '');
        if(!empty($provider)) {
            $groups->where('provider_id', $provider);
        }

        $search = $request->query('search', '');
        if(!empty($search)) {
            $groups->where(function ($query) use ($search) {
                $query->where('id_at_provider', 'LIKE', $search . '%')
                    ->orWhere(DB::raw("CONCAT(bride_first_name, ' ', bride_last_name, ' ', groom_first_name, ' ', groom_last_name)"), 'LIKE', '%' . $search . '%')
                    ->orWhereHas('bookings.clients', function($query) use ($search) {
                        $query->whereHas('client', function ($query) use ($search) {
                                $query->where('email', $search);
                            })
                            ->orWhereHas('guests', function ($query) use ($search) {
                                $query->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', '%' . $search . '%');
                            });
                    });
            });
        }

        return GroupResource::collection($groups->paginate($request->query('paginate', 10)))
            ->additional([
                'agents' => TravelAgent::select(
                    'travel_agents.id AS value',
                    DB::raw("CONCAT(travel_agents.first_name, ' ', travel_agents.last_name) AS text")
                )->get(),
                'providers' => Provider::select(
                    'providers.id AS value',
                    'providers.name AS text'
                )->get(),
                'can' => [
                    'create' => $request->user()->can('create', Group::class),
                ]
            ]);
    }
}