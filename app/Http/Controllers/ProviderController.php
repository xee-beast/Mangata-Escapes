<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProvider;
use App\Http\Requests\UpdateProvider;
use App\Http\Requests\UpdateSpecialists;
use App\Http\Resources\ProviderResource;
use App\Models\Provider;
use App\Models\Specialist;
use Illuminate\Http\Request;

class ProviderController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Provider::class, 'provider');
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $providers = Provider::query();
        $search = $request->query('search', '');

        if (!empty($search)) {
            $providers->where('name', 'LIKE', '%' . $search . '%')
                ->orWhere('abbreviation', 'LIKE', '%' . $search . '%')
                ->orWhere('phone_number', 'LIKE', '%' . $search . '%')
                ->orWhere('email', 'LIKE', '%' . $search . '%');
        }

        return ProviderResource::collection($providers->paginate($request->query('paginate', 10)))
            ->additional([
                'can' => [
                    'create' => $request->user()->can('create', Provider::class),
                ]
            ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreProvider  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreProvider $request)
    {
        $provider = new Provider;

        $provider->name = $request->input('name');
        $provider->abbreviation = $request->input('abbreviation');
        $provider->phone_number = $request->input('phoneNumber');
        $provider->email = $request->input('email');

        $provider->save();

        return (new ProviderResource($provider))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Provider  $provider
     * @return \Illuminate\Http\Response
     */
    public function show(Provider $provider)
    {
        $provider->loadMissing(['specialists' => function ($query) {
            $query->withCount('leadProviders');
        }]);

        return new ProviderResource($provider);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateProvider  $request
     * @param  \App\Models\Provider  $provider
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateProvider $request, Provider $provider)
    {
        $provider->name = $request->input('name');
        $provider->abbreviation = $request->input('abbreviation');
        $provider->phone_number = $request->input('phoneNumber');
        $provider->email = $request->input('email');

        $provider->save();

        return new ProviderResource($provider);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Provider  $provider
     * @return \Illuminate\Http\Response
     */
    public function destroy(Provider $provider)
    {
        $provider->delete();

        return response()->json()->setStatusCode(204);
    }

    public function updateSpecialists(UpdateSpecialists $request, Provider $provider)
    {
        $this->authorize('update', $provider);

        $specialistIds = [];

        foreach ($request->input('specialists', []) as $specialistData) {
            $specialist = isset($specialistData['id'])
                ? Specialist::find($specialistData['id'])
                : null;

            if ($specialist) {
                $specialist->update([
                    'name' => $specialistData['name'],
                    'email' => $specialistData['email'],
                ]);
            } else {
                $specialist = $provider->specialists()->create([
                    'name' => $specialistData['name'],
                    'email' => $specialistData['email'],
                ]);
            }

            $specialistIds[] = $specialist->id;
        }

        $provider->specialists()->whereNotIn('id', $specialistIds)->delete();

        return response()->json()->setStatusCode(200);
    }
}
