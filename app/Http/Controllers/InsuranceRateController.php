<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInsuranceRate;
use App\Http\Requests\UpdateInsuranceRate;
use App\Http\Resources\InsuranceRateResource;
use App\Http\Resources\ProviderResource;
use App\Models\InsuranceRate;
use App\Models\Provider;
use Illuminate\Http\Request;

class InsuranceRateController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(InsuranceRate::class, 'insuranceRate');
    }

    /**
     * Display a listing of the resource.
     *
     * @param \App\Models\Provider
     * @return \Illuminate\Http\Response
     */
    public function index(Provider $provider)
    {
        return InsuranceRateResource::collection($provider->insurance_rates()->orderBy('start_date', 'desc')->orderBy('name')->get())->additional([
            'provider' => new ProviderResource($provider),
            'can' => [
                'create' => auth()->user()->can('create', Provider::class),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \App\Models\Provider
     * @param  \App\Http\Requests\StoreInsuranceRate  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Provider $provider, StoreInsuranceRate $request)
    {
        $insuranceRate = $provider->insurance_rates()->create([
            'name' => $request->input('name'),
            'start_date' => $request->input('startDate'),
            'description' => "Travel Insurance is available for all US-based travelers and highly recommended. Rates are per person, depending on the cost of the land-only portion of your trip. Travel Insurance can be added at any point; however, in order to receive the full benefit of the travel insurance, you must add it within {$request->input('cfar')} days of booking. Otherwise, the \"Cancel For Any Reason\" benefit will not apply and the only benefits will be for in-travel issues (per New York State law, CFAR does not apply to any NY residents). Please note that once purchased, travel insurance is nonrefundable.",
            'type' => $request->input('calculateBy', 'total'),
            'rates' => array_map(function ($rate) {
                return [
                    'to' => $rate['to'],
                    'rate' => $rate['rate'],
                ];
            }, $request->input('rates')),
            'url' => $request->input('url')
        ]);

        return (new InsuranceRateResource($insuranceRate))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Provider
     * @param  \App\Models\InsuranceRate  $insuranceRate
     * @return \Illuminate\Http\Response
     */
    public function show(Provider $provider, InsuranceRate $insuranceRate)
    {
        return (new InsuranceRateResource($insuranceRate))->additional([
            'provider' => new ProviderResource($provider)
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \App\Models\Provider
     * @param  \App\Models\InsuranceRate  $insuranceRate
     * @param  \App\Http\Requests\UpdateInsuranceRate  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Provider $provider, InsuranceRate $insuranceRate, UpdateInsuranceRate $request)
    {
        $insuranceRate->update([
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'start_date' => $request->input('startDate'),
            'type' => $request->input('calculateBy', 'total'),
            'rates' => array_map(function ($rate) {
                return [
                    'to' => $rate['to'],
                    'rate' => $rate['rate'],
                ];
            }, $request->input('rates')),
            'url' => $request->input('url')
        ]);

        return new InsuranceRateResource($insuranceRate);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Provider
     * @param  \App\Models\InsuranceRate  $insuranceRate
     * @return \Illuminate\Http\Response
     */
    public function destroy(Provider $provider, InsuranceRate $insuranceRate)
    {
        $insuranceRate->delete();

        return response()->json()->setStatusCode(204);
    }
}
