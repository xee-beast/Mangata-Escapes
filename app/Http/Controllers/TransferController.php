<?php

namespace App\Http\Controllers;

use App\Http\Resources\TransferResource;
use App\Models\Transfer;
use App\Http\Controllers\Traits\GetImages;
use Illuminate\Http\Request;

class TransferController extends Controller
{
    use GetImages;

    public function index(Request $request)
    {
        $transfers = Transfer::query();

        $search = $request->query('search', '');

        if (!empty($search)) {
            $transfers->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('primary_phone_number', 'like', "%{$search}%");
            });
        }

        $transfers->orderBy('id', 'desc');

        return TransferResource::collection($transfers->paginate($request->query('paginate', 10)))
            ->additional([
                'can' => [
                    'create' => auth()->user()->can('manage transfers'),
                ]
            ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:transfers,email|max:255',
            'primaryPhoneNumber' => 'required|string|max:20',
            'secondaryPhoneNumberLabel' => 'nullable|string|max:255',
            'secondaryPhoneNumberValue' => 'nullable|string|max:20',
            'whatsappNumber' => 'nullable|string|max:20',
            'missedOrChangedFlight' => 'required|string|max:3000',
            'arrivalProcedure' => 'required|string|max:3000',
            'departureProcedure' => 'required|string|max:3000',
            'displayImage' => 'nullable|array',
            'displayImage.uuid' => 'required_with:displayImage|uuid',
            'displayImage.path' => 'required_with:displayImage|string',
            'appImage' => 'nullable|array',
            'appImage.uuid' => 'required_with:appImage|uuid',
            'appImage.path' => 'required_with:appImage|string',
            'appLink' => 'nullable|url|max:255',
        ]);

        $transfer = new Transfer();
        $transfer->name = $request->input('name');
        $transfer->email = $request->input('email');
        $transfer->primary_phone_number = $request->input('primaryPhoneNumber');
        $transfer->secondary_phone_number_label = $request->input('secondaryPhoneNumberLabel');
        $transfer->secondary_phone_number_value = $request->input('secondaryPhoneNumberValue');
        $transfer->whatsapp_number = $request->input('whatsappNumber');
        $transfer->missed_or_changed_flight = $request->input('missedOrChangedFlight');
        $transfer->arrival_procedure = $request->input('arrivalProcedure');
        $transfer->departure_procedure = $request->input('departureProcedure');

        if (is_array($request->input('displayImage'))) {
            $transfer->display_image()->associate($this->getImage($request->input('displayImage'))->id);
        }

        if (is_array($request->input('appImage'))) {
            $transfer->app_image()->associate($this->getImage($request->input('appImage'))->id);
        }

        $transfer->app_link = $request->input('appLink');
        $transfer->save();

        return (new TransferResource($transfer))->response()->setStatusCode(201);
    }

    public function update(Request $request, Transfer $transfer)
    {
         $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:transfers,email,' . $transfer->id,
            'primaryPhoneNumber' => 'required|string|max:20',
            'secondaryPhoneNumberLabel' => 'nullable|string|max:255',
            'secondaryPhoneNumberValue' => 'nullable|string|max:20',
            'whatsappNumber' => 'nullable|string|max:20',
            'missedOrChangedFlight' => 'required|string|max:3000',
            'arrivalProcedure' => 'required|string|max:3000',
            'departureProcedure' => 'required|string|max:3000',
            'displayImage' => 'nullable|array',
            'displayImage.uuid' => 'required_with:displayImage|uuid',
            'displayImage.path' => 'required_with:displayImage|string',
            'appImage' => 'nullable|array',
            'appImage.uuid' => 'required_with:appImage|uuid',
            'appImage.path' => 'required_with:appImage|string',
            'appLink' => 'nullable|url|max:255',
        ]);

        $transfer->name = $request->input('name');
        $transfer->email = $request->input('email');
        $transfer->primary_phone_number = $request->input('primaryPhoneNumber');
        $transfer->secondary_phone_number_label = $request->input('secondaryPhoneNumberLabel');
        $transfer->secondary_phone_number_value = $request->input('secondaryPhoneNumberValue');
        $transfer->whatsapp_number = $request->input('whatsappNumber');
        $transfer->missed_or_changed_flight = $request->input('missedOrChangedFlight');
        $transfer->arrival_procedure = $request->input('arrivalProcedure');
        $transfer->departure_procedure = $request->input('departureProcedure');

        if (is_array($request->input('displayImage'))) {
            $transfer->display_image()->associate($this->getImage($request->input('displayImage'))->id);
        } else {
            $transfer->display_image()->dissociate();
        }

        if (is_array($request->input('appImage'))) {
            $transfer->app_image()->associate($this->getImage($request->input('appImage'))->id);
        } else {
            $transfer->app_image()->dissociate();
        }

        $transfer->app_link = $request->input('appLink');
        $transfer->save();

        return new TransferResource($transfer->load(['display_image', 'app_image']));
    }

    public function show(Transfer $transfer)
    {
        return new TransferResource($transfer->load(['display_image', 'app_image']));
    }

    public function destroy(Transfer $transfer)
    {
        $transfer->groupAirports()->update(['transfer_id' => null]);
        $transfer->bookings()->withTrashed()->update(['transfer_id' => null]);
        $transfer->delete();

        return response()->json()->setStatusCode(204);
    }
}
