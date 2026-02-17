<?php

namespace App\Http\Controllers;

use App\Http\Resources\AirportResource;
use App\Models\Airport;
use App\Models\Transfer;
use Illuminate\Http\Request;

class AirportController extends Controller
{
    public function index(Request $request)
    {
        $airports = Airport::with('transfer');

        $search = $request->query('search', '');
        
        if (!empty($search)) {
            $airports = $airports->where(function($query) use ($search) {
                $query->where('airport_code', 'like', "%{$search}%")
                    ->orWhere('timezone', 'like', "%{$search}%");
            });
        }
        
        $airports->orderBy('id', 'desc');

        return AirportResource::collection($airports->paginate($request->query('paginate', 10)))
            ->additional([
                'can' => [
                    'create' => auth()->user()->can('manage airports'),
                ],
                'timezoneOptions' => config('timezones'),
                'transfers' => Transfer::select('id', 'name')->get(),
            ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'airport_code' => 'required|string|unique:airports,airport_code|max:255',
            'timezone' => 'required|string|max:255',
            'transfer_id' => 'nullable|exists:transfers,id',
        ]);

        $airport = new Airport();
        $airport->airport_code = $request->input('airport_code');
        $airport->timezone = $request->input('timezone');
        $airport->transfer_id = $request->input('transfer_id');
        $airport->save();
        
        return (new AirportResource($airport))->response()->setStatusCode(201);
    }

    public function update(Request $request, Airport $airport)
    {
        $request->validate([
            'airport_code' => 'required|string|unique:airports,airport_code,' . $airport->id . '|max:255',
            'timezone' => 'required|string|max:255',
            'transfer_id' => 'nullable|exists:transfers,id',
        ]);

        $airport->airport_code = $request->input('airport_code');
        $airport->timezone = $request->input('timezone');
        $airport->transfer_id = $request->input('transfer_id');
        $airport->save();

        return (new AirportResource($airport));
    }

    public function destroy(Airport $airport)
    {
        if ($airport->destination->count() > 0) {
            return response()->json([
                'message' => 'Airport has destinations and cannot be deleted.'
            ], 422);
        }

        if ($airport->groups->count() > 0) {
            return response()->json([
                'message' => 'Airport has groups and cannot be deleted.'
            ], 422);
        }

        $airport->delete();
        return response()->json()->setStatusCode(204);
    }
}
