<?php

namespace App\Http\Controllers;

use App\Http\Resources\AirlineResource;
use App\Models\Airline;
use Illuminate\Http\Request;

class AirlineController extends Controller
{
    public function index(Request $request)
    {
        $airlines = Airline::query();

        $search = $request->query('search', '');
        
        if (!empty($search)) {
            $airlines = $airlines->where('name', 'like', "%{$search}%");
        }
        
        $airlines->orderBy('id', 'desc');

        return AirlineResource::collection($airlines->paginate($request->query('paginate', 10)))
            ->additional([
                'can' => [
                    'create' => auth()->user()->can('manage airlines'),
                ]
            ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:airlines,name|max:255',
            'iata_code' => 'required|string|unique:airlines,iata_code|max:255',
        ]);

        $airline = new Airline();
        $airline->name = $request->input('name');
        $airline->iata_code = $request->input('iata_code');
        $airline->save();
        
        return (new AirlineResource($airline))->response()->setStatusCode(201);
    }

    public function update(Request $request, Airline $airline)
    {
        $request->validate([
            'name' => 'required|string|unique:airlines,name,' . $airline->id . '|max:255',
            'iata_code' => 'required|string|unique:airlines,iata_code,' . $airline->id . '|max:255',
        ]);

        $airline->name = $request->input('name');
        $airline->iata_code = $request->input('iata_code');
        $airline->save();

        return (new AirlineResource($airline));
    }

    public function destroy(Airline $airline)
    {
        $airline->delete();
        return response()->json()->setStatusCode(204);
    }
}
