<?php

namespace App\Http\Controllers;

use App\Http\Resources\TravelAgentResource;
use App\Http\Resources\UserResource;
use App\Http\Requests\StoreTravelAgent;
use App\Http\Requests\UpdateTravelAgent;
use App\Models\TravelAgent;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TravelAgentController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(TravelAgent::class, 'agent');
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $agents = TravelAgent::query();

        $search = $request->query('search', '');
        if (!empty($search)) {
            $agents->where(function ($query) use ($search) {
                $query->where(DB::raw("CONCAT(travel_agents.first_name, ' ', travel_agents.last_name)"), 'like', '%' . $search . '%')
                    ->orWhereHas('user', function ($subQuery) use ($search) {
                        $subQuery->where(DB::raw("CONCAT(users.first_name, ' ', users.last_name)"), 'like', '%' . $search . '%');
                    });
            });
        }

        $status = $request->query('status', '');
        if ($status === 'active') {
            $agents->where('is_active', true);
        } elseif ($status === 'inactive') {
            $agents->where('is_active', false);
        }

        $search = $request->query('search', '');
        if (!empty($search)) {
            $agents->where(function ($query) use ($search) {
                $query->where(DB::raw("CONCAT(travel_agents.first_name, ' ', travel_agents.last_name)"), 'like', '%' . $search . '%')
                    ->orWhereHas('user', function ($subQuery) use ($search) {
                        $subQuery->where(DB::raw("CONCAT(users.first_name, ' ', users.last_name)"), 'like', '%' . $search . '%');
                    });
            });
        }

        return TravelAgentResource::collection($agents->paginate($request->query('paginate', 10)))
            ->additional([
                'users' => UserResource::collection(User::doesntHave('travel_agent')->where('email_verified_at', '!=', null)->get()),
                'can' => [
                    'create' => $request->user()->can('create', TravelAgent::class),
                    'viewGroups' => $request->user()->can('viewAny', \App\Models\Group::class),
                    'viewBookings' => $request->user()->can('viewAny', \App\Models\Booking::class),
                ],
            ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreTravelAgent  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreTravelAgent $request)
    {
        $agent = TravelAgent::create([
            'user_id' => $request->input('user'),
            'first_name' => $request->input('firstName'),
            'last_name' => $request->input('lastName'),
            'email' => $request->input('email'),
            'is_active' => $request->input('isActive', true),
        ]);

        return (new TravelAgentResource($agent))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\TravelAgent  $travelAgent
     * @return \Illuminate\Http\Response
     */
    public function show(TravelAgent $agent)
    {
        return new TravelAgentResource($agent);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Models\TravelAgent  $agent
     * @param  \App\Http\Requests\UpdateTravelAgent  $request
     * @return \Illuminate\Http\Response
     */
    public function update(TravelAgent $agent, UpdateTravelAgent $request)
    {
        $agent->update([
            'first_name' => $request->input('firstName'),
            'last_name' => $request->input('lastName'),
            'email' => $request->input('email'),
            'is_active' => $request->input('isActive', $agent->is_active),
        ]);

        return new TravelAgentResource($agent);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\TravelAgent  $agent
     * @return \Illuminate\Http\Response
     */
    public function destroy(TravelAgent $agent)
    {
        $agent->delete();

        return response()->noContent();
    }

    /**
     * Enable the specified travel agent.
     *
     * @param  \App\Models\TravelAgent  $agent
     * @return \Illuminate\Http\Response
     */
    public function enable(TravelAgent $agent)
    {
        $this->authorize('update', $agent);
        
        $agent->update(['is_active' => true]);
        
        return new TravelAgentResource($agent);
    }

    /**
     * Disable the specified travel agent.
     *
     * @param  \App\Models\TravelAgent  $agent
     * @return \Illuminate\Http\Response
     */
    public function disable(TravelAgent $agent)
    {
        $this->authorize('update', $agent);
        
        $agent->update(['is_active' => false]);
        
        return new TravelAgentResource($agent);
    }
}
