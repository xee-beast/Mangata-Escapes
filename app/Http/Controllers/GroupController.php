<?php

namespace App\Http\Controllers;

use App\Exports\BookingExport;
use App\Exports\FlightManifestExport;
use App\Exports\RoomingListComparisonExport;
use App\Http\Controllers\Traits\GetImages;
use App\Http\Requests\SendBulkGroupEmail;
use App\Http\Requests\SendGroupEmail;
use App\Http\Requests\StoreGroup;
use App\Http\Requests\UpdateGroup;
use App\Http\Requests\UpdateGroupDueDates;
use App\Http\Requests\UpdateGroupPastBride;
use App\Http\Resources\GroupResource;
use App\Models\Booking;
use App\Models\Destination;
use App\Models\DueDate;
use App\Models\Group;
use App\Models\GroupAttritionDueDate;
use App\Models\InsuranceRate;
use App\Models\Provider;
use App\Models\Transfer;
use App\Models\TravelAgent;
use App\Notifications\BrideGroupEmail;
use App\Notifications\GroupEmail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Notifications\SendGroupPasswordNotification;  
use App\Notifications\SendCouplesSitePasswordNotification;
use Illuminate\Support\Facades\Validator;
use App\Services\RoomingListComparisonService;

class GroupController extends Controller
{
    use GetImages;

    public function __construct()
    {
        $this->authorizeResource(Group::class, 'group');
    }
    
    /**
     * Toggle the booking acceptance status for the specified group.
     *
     * @param  \App\Models\Group  $group
     * @return \Illuminate\Http\Response
     */
    public function toggleBookingAcceptance(Group $group)
    {
        $this->authorize('update', $group);
        
        $group->update([
            'accepts_new_bookings' => !$group->accepts_new_bookings
        ]);
        
        return response()->json([
            'accepts_new_bookings' => $group->accepts_new_bookings,
            'message' => $group->accepts_new_bookings 
                ? 'Group is now accepting new bookings.' 
                : 'Group is no longer accepting new bookings.'
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $search = $request->query('search', '');

        if(!empty($search)) {
            $booking = Booking::withTrashed()
                ->whereHas('clients', function($query) use ($search) {
                    $query->where('reservation_code', $search);
                })->first();

            if(!empty($booking)) {
                return [
                    'group' => $booking->group_id,
                    'booking' => $booking->id,
                ];
            }
        }

        $groups = Group::query()->with(['destination', 'travel_agent', 'provider', 'hotels']);

        if ('false' == $request->query('old', false)) {
            $groups->whereDate('event_date', '>', Carbon::now());
        }

        $fit = $request->query('fit', '');
        if ($fit !== null && $fit !== '') {
            $groups->where('is_fit', $fit);
        }

        $agent = $request->query('agent', '');
        if(!empty($agent)) {
            $groups->where('travel_agent_id', $agent);
        }

        $provider = $request->query('provider', '');
        if(!empty($provider)) {
            $groups->where('provider_id', $provider);
        }

        $year = $request->query('year', '');
        if(!empty($year)) {
            $groups->whereYear('event_date', $year);
        }

        $search = $request->query('search', '');
        if(!empty($search)) {
            $groups->where(function ($query) use ($search) {
                $query->where('id_at_provider', 'LIKE', $search . '%')
                    ->orWhere(DB::raw("CONCAT(bride_first_name, ' ', bride_last_name, ' ', groom_first_name, ' ', groom_last_name)"), 'LIKE', '%' . $search . '%')
                    ->orWhereHas('bookingsWithTrashed.clients', function($query) use ($search) {
                        $query->whereHas('client', function ($query) use ($search) {
                                $query->where('email', $search);
                            })
                            ->orWhereHas('guests', function ($query) use ($search) {
                                $query->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', '%' . $search . '%');
                            });
                    })
                    ->orWhereHas('hotels.hotel', function($query) use ($search) {
                        $query->where('name', 'LIKE', '%' . $search . '%');
                    });
            });
        }

        $groups->whereNull('deleted_at');

        $groups->orderBy('event_date');

        return GroupResource::collection($groups->paginate($request->query('paginate', 25)))
            ->additional([
                'destinations' => Destination::select(
                    'destinations.id AS value',
                    DB::raw("CONCAT(destinations.name, ', ', countries.name) AS text"))->join('countries', 'destinations.country_id', '=', 'countries.id'
                )->get(),
                'agents' => TravelAgent::active()->select(
                    'travel_agents.id AS value',
                    DB::raw("CONCAT(travel_agents.first_name, ' ', travel_agents.last_name) AS text")
                )->get(),
                'providers' => Provider::select(
                    'providers.id AS value',
                    'providers.name AS text'
                )->get(),
                'can' => [
                    'create' => $request->user()->can('create', Group::class),
                ],
                'insuranceRates' => InsuranceRate::select(
                    'id AS value',
                    'name AS text',
                    'provider_id AS provider'
                )->get(),
                'years' => Group::selectRaw('DISTINCT YEAR(event_date) as year')
                    ->whereNull('deleted_at')
                    ->where(function($query) use ($request) {
                        if ('false' == $request->query('old', false)) {
                            $query->whereDate('event_date', '>', Carbon::now());
                        }
                    })
                    ->orderBy('year', 'desc')
                    ->pluck('year')
                    ->map(function($year) {
                        return ['value' => $year, 'text' => $year];
                    }),
            ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreGroup  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreGroup $request)
    {
        $group = new Group;

        $group->destination_id = $request->input('destination');
        $group->is_fit = $request->input('fit');
        $group->wedding_location = $request->input('weddingLocation', 'resort');

        if ($request->input('weddingLocation') == 'venue') {
            $group->venue_name = $request->input('venueName');
        }

        $group->event_date = $request->input('eventDate');
        $group->bride_first_name = $request->input('brideFirstName');
        $group->bride_last_name = $request->input('brideLastName');
        $group->groom_first_name = $request->input('groomFirstName');
        $group->groom_last_name = $request->input('groomLastName');
        $group->email = $request->input('email');
        $group->secondary_email = $request->input('secondaryEmail');
        $group->password = $request->input('password');
        $group->slug = $request->input('slug');
        $group->is_active = $request->input('isActive', false);
        $group->couples_site_password = $request->input('couplesSitePassword');

        if (is_array($request->input('image'))) {
            $group->image()->associate($this->getImage($request->input('image'))->id);
        }

        $group->message = $request->input('message');
        $group->travel_agent_id = $request->input('agent');
        $group->provider_id = $request->input('provider');
        $group->id_at_provider = $request->input('providerId');
        $group->insurance_rate_id = $request->input('insuranceRate');
        $group->use_fallback_insurance = $request->input('useFallbackInsurance');
        $group->transportation = false;
        $group->min_nights = $request->input('minNights');
        $group->deposit = $request->input('deposit');
        $group->deposit_type = $request->input('depositType', 'fixed');
        $group->change_fee_amount = $request->input('changeFeeAmount');
        $group->change_fee_date = $request->input('changeFeeDate');
        $group->cancellation_date = $request->input('cancellationDate');
        $group->balance_due_date = $request->input('dueDate');
        $group->notes = $request->input('notes');
        $group->disable_invoice_splitting = $request->input('disableInvoiceSplitting', false);
        $group->disable_notifications = $request->input('disableNotifications', false);

        $group->save();
        
        return (new GroupResource($group))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Group  $group
     * @return \Illuminate\Http\Response
     */
    public function show(Group $group)
    {
        return (new GroupResource($group->loadMissing(['groupAttritionDueDates', 'groupFaqs', 'destination', 'travel_agent', 'provider', 'insurance_rate', 'image', 'attrition_image', 'due_dates', 'hotels', 'airports'])))
            ->additional([
                'destinations' => Destination::select(
                    'destinations.id AS value',
                    DB::raw("CONCAT(destinations.name, ', ', countries.name) AS text"))->join('countries', 'destinations.country_id', '=', 'countries.id'
                )->get(),
                'agents' => TravelAgent::active()->select(
                    'travel_agents.id AS value',
                    DB::raw("CONCAT(travel_agents.first_name, ' ', travel_agents.last_name) AS text")
                )->get(),
                'providers' => Provider::select(
                    'providers.id AS value',
                    'providers.name AS text'
                )->get(),
                'insuranceRates' => InsuranceRate::select(
                    'id AS value',
                    'name AS text',
                    'provider_id AS provider'
                )->get(),
                'airports' => Destination::with('airports')->get(),
                'transfers' => Transfer::all(),
                'years' => Group::selectRaw('DISTINCT YEAR(event_date) as year')
                    ->whereNull('deleted_at')
                    ->orderBy('year', 'desc')
                    ->pluck('year')
                    ->map(function($year) {
                        return ['value' => $year, 'text' => $year];
                    }),
            ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateGroup  $request
     * @param  \App\Models\Group  $group
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateGroup $request, Group $group)
    {
        if (count($group->bookings()->withTrashed()->get()) == 0) {
            $group->destination_id = $request->input('destination');
        }

        $group->wedding_location = $request->input('weddingLocation', 'resort');

        if ($request->input('weddingLocation') == 'venue') {
            $group->venue_name = $request->input('venueName');
        }

        $group->event_date = $request->input('eventDate');
        $group->bride_first_name = $request->input('brideFirstName');
        $group->bride_last_name = $request->input('brideLastName');
        $group->groom_first_name = $request->input('groomFirstName');
        $group->groom_last_name = $request->input('groomLastName');
        $group->email = $request->input('email');
        $group->secondary_email = $request->input('secondaryEmail');
        $group->password = $request->input('password');
        $group->slug = $request->input('slug');
        $group->is_active = $request->input('isActive', false);
        $group->couples_site_password = $request->input('couplesSitePassword');
        
        if (is_array($request->input('image'))) {
            $group->image()->associate($this->getImage($request->input('image'))->id);
        } else {
            $group->image()->dissociate();
        }
        
        $group->message = $request->input('message');
        $group->travel_agent_id = $request->input('agent');
        $group->provider_id = $request->input('provider');
        $group->id_at_provider = $request->input('providerId');
        $group->insurance_rate_id = $request->input('insuranceRate');
        $group->use_fallback_insurance = $request->input('useFallbackInsurance');
        $group->transportation = $request->input('transportation');
        $group->min_nights = $request->input('minNights');
        $group->deposit = $request->input('deposit');
        $group->deposit_type = $request->input('depositType', 'fixed');
        $group->change_fee_amount = $request->input('changeFeeAmount');
        $group->change_fee_date = $request->input('changeFeeDate');
        $group->notes = $request->input('notes');
        $group->banner_message = $request->input('bannerMessage');
        $group->staff_message = $request->input('staffMessage');

        if ($request->input('transportation')) {
            $group->transportation_rate = $request->input('transportationRate');
            $group->single_transportation_rate = $request->input('singleTransportationRate');
            $group->one_way_transportation_rate = $request->input('oneWayTransportationRate');
            $group->transportation_type = $request->input('transportationType', 'private');
            $group->transportation_submit_before = $request->input('transportationSubmitBefore');
        }

        $group->disable_invoice_splitting = $request->input('disableInvoiceSplitting', false);
        $group->disable_notifications = $request->input('disableNotifications', false);

        $group->save();

        $airportSync = [];

        if ($request->input('transportation')) {        
            foreach($request->input('airports') as $airport) {
                $groupAirport = $group->airports()->updateOrCreate(
                    ['airport_id' => $airport['airport']],
                    [
                        'transfer_id' => $airport['transfer'] ?? null,
                        'transportation_rate' => $airport['transportationRate'],
                        'single_transportation_rate' => $airport['singleTransportationRate'],
                        'one_way_transportation_rate' => $airport['oneWayTransportationRate'],
                        'default' => $airport['default'],
                    ]
                );

                array_push($airportSync, $groupAirport->id);
            }
            $group->airports()->whereNotIn('id', $airportSync)->delete();
        }

        foreach ($group->bookings as $booking) {
            $booking->total <= $booking->payment_total ? $booking->is_paid = true : $booking->is_paid = false;
            $booking->save();
        }

        return new GroupResource($group->loadMissing(['destination', 'travel_agent', 'provider', 'image']));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Group  $group
     * @return \Illuminate\Http\Response
     */
    public function destroy(Group $group)
    {
        // $group->airports()->delete();
        $group->delete();

        return response()->json()->setStatusCode(204);
    }

    /**
     * Sync the groups due dates.
     *
     * @param  \App\Http\Requests\SyncDueDates  $request
     * @param  \App\Models\Group  $group
     * @return \Illuminate\Http\Response
     */
    public function syncDueDates(UpdateGroupDueDates $request, Group $group)
    {
        $group->cancellation_date = $request->input('cancellationDate');
        $group->balance_due_date = $request->input('dueDate');
        $group->save();

        $dueDateSync = [];
        foreach ($request->input('other') as $dueDateInput) {
            $dueDate = $group->due_dates()->updateOrCreate(
                ['group_id' => $group->id, 'date' => Carbon::parse($dueDateInput['date'])->format('Y-m-d')],
                ['amount' => $dueDateInput['amount'], 'type' => $dueDateInput['type']]
            );
            array_push($dueDateSync, $dueDate->id);
        }
        DueDate::where('group_id', $group->id)->whereNotIn('id', $dueDateSync)->delete();

        return response()->json()->setStatusCode(204);
    }

    public function updatePastBride(UpdateGroupPastBride $request, Group $group)
    {
        $group->past_bride_message = $request->input('message');
        $group->show_as_past_bride = $request->input('show') ?? 0;
        $group->save();

        return response()->json()->setStatusCode(204);
    }

    /**
     * Download an excel with all the bookings of a specified group.
     *
     * @param \App\Models\Group $group
     * @return \Maatwebsite\Excel\Facades\Excel
     */
    public function exportBookingsToExcel(Group $group)
    {
        $today = today()->format('m-d-Y');

        return (new BookingExport($group))->download("{$group->formalName} Bookings ({$today}).xlsx", \Maatwebsite\Excel\Excel::XLSX, [
            'X-Vapor-Base64-Encode' => 'True'
        ]);
    }

    public function exportFlightManifestsToExcel(Group $group)
    {
        $today = today()->format('m-d-Y');

        return (new FlightManifestExport($group))->download("{$group->formalName} Flight Manifests ({$today}).xlsx", \Maatwebsite\Excel\Excel::XLSX, [
            'X-Vapor-Base64-Encode' => 'True'
        ]);
    }

    /**
     * Export rooming list comparison report to Excel
     *
     * @param Group $group
     * @param Request $request
     * @param RoomingListComparisonService $comparisonService
     * @return \Maatwebsite\Excel\Facades\Excel
     */
    public function exportRoomingListComparison(Group $group, Request $request, RoomingListComparisonService $comparisonService)
    {
        $this->authorize('update', $group);

        $request->validate([
            'file' => 'required|file|mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,application/octet-stream|max:10240',
        ]);

        try {
            $file = $request->file('file');
            $filePath = $file->getRealPath();
            
            // Compare the spreadsheet with system data
            $report = $comparisonService->compareRoomingList($group, $filePath);
            
            $today = today()->format('m-d-Y');
            
            return (new RoomingListComparisonExport($report))->download(
                "{$group->formalName} Rooming List Comparison ({$today}).xlsx",
                \Maatwebsite\Excel\Excel::XLSX,
                ['X-Vapor-Base64-Encode' => 'True']
            );
            
        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
            return response()->json([
                'message' => 'Failed to read the spreadsheet. Please ensure it is a valid Excel file.',
                'errors' => ['file' => ['Invalid spreadsheet format.']]
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while processing the file: ' . $e->getMessage(),
                'errors' => ['file' => [$e->getMessage()]]
            ], 422);
        }
    }

    /**
     * Send message to group clients or bride.
     *
     * @param \App\Models\Group $group
     * @param \App\Http\Requests\SendGroupEmail $request
     * @return \Illuminate\Http\Response
     */
    public function sendEmail(Group $group, SendGroupEmail $request)
    {
        // Send to all clients
        $group->clients->each(function ($client) use ($request) {
            $client->client->notify(new GroupEmail($client->client, $request->validated()));
        });        

        return response()->json()->setStatusCode(204);
    }

    /**
     * Sync attrition image.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Group  $group
     * @return \Illuminate\Http\Response
     */
    public function syncAttrition(Request $request, Group $group)
    {
        if (is_array($request->input('attritionImage'))) {
            $group->attrition_image()->associate($this->getImage($request->input('attritionImage'))->id);
        } else {
            $group->attrition_image()->dissociate();
        }

        $group->save();

        if ($request->has('attritionDueDates')) {
            $group->groupAttritionDueDates()->delete();

            foreach ($request->attritionDueDates as $attritionDueDate) {
                if ($attritionDueDate['date'] !== null) {
                    GroupAttritionDueDate::create([
                        'group_id' => $group->id,
                        'date' => $attritionDueDate['date'],
                    ]);
                }
            }
        }
        
        return response()->json()->setStatusCode(204);
    }

    /**
     * Send message to multiple groups based on filters.
     *
     * @param \App\Http\Requests\SendBulkGroupEmail $request
     * @return \Illuminate\Http\Response
     */
    public function sendBulkEmail(SendBulkGroupEmail $request)
    {    
        $groups = Group::query();
        $groups->whereDate('event_date', '>', Carbon::now());

        // Send emails
        $groups->chunk(100, function($collection) use ($request) {
            foreach ($collection as $group) {
                // Send to bride
                $group->notify(new BrideGroupEmail($group, $request->validated()));
            }
        });

        return response()->json()->setStatusCode(204);
    }

    public function updateFaqs(Request $request, Group $group)
    {
        $request->validate([
            'faqs' => 'required|array',
            'faqs.*.title' => 'required|string|max:1000',
            'faqs.*.description' => 'required|string|max:10000',
        ]);

        $group->groupFaqs()->delete();

        foreach ($request->faqs as $faq) {
            $group->groupFaqs()->create([
                'title' => $faq['title'],
                'description' => $faq['description'],
                'type' => $faq['type'],
            ]);
        }

        return response()->json()->setStatusCode(204);
    }
    
    public function restore(Group $group) 
    {
        try {
            $group->restore();
            return (new GroupResource($group))->response()->setStatusCode(201);

        } catch(Exception $e) {
            return response()->json()->setStatusCode(500);
        }
    }

    /**
     * Update the terms and conditions for a group.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Group  $group
     * @return \Illuminate\Http\Response
     */
    public function updateTermsConditions(Request $request, Group $group)
    {
        $request->validate([
            'termsAndConditions' => 'nullable|string',
        ]);

        $terms = $request->input('termsAndConditions');
        $stripped = strip_tags($terms);

        $group->terms_and_conditions = (trim($stripped) === '') ? $group->getDefaultTerms() : $terms;
        $group->save();

        return response()->json([
            'termsAndConditions' => $group->terms_and_conditions
        ], 200);
    }

    public function sendGroupLeaderEmail(Group $group){
        $validator = Validator::make(
            ['password' => $group->password],
            ['password' => 'required']
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Group leader password is not set.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $group->notify(new SendGroupPasswordNotification($group));
    }

    public function sendCouplesSitePasswordEmail(Group $group){
        $validator = Validator::make(
            ['couplesSitePassword' => $group->couples_site_password],
            ['couplesSitePassword' => 'required']
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Couples site password is not set.',
                'errors' => $validator->errors(),
            ], 422);
        }
        
                
        $group->notify(new SendCouplesSitePasswordNotification($group));
    }

    /**
     * Review rooming list spreadsheet and compare with system data
     *
     * @param Group $group
     * @param Request $request
     * @param RoomingListComparisonService $comparisonService
     * @return \Illuminate\Http\JsonResponse
     */
    public function reviewRoomingList(Group $group, Request $request, RoomingListComparisonService $comparisonService)
    {
        $this->authorize('update', $group);

        $request->validate([
            'file' => 'required|file|mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,application/octet-stream|max:10240', // Max 10MB
        ]);

        try {
            $file = $request->file('file');
            $filePath = $file->getRealPath();
            
            // Compare the spreadsheet with system data
            $report = $comparisonService->compareRoomingList($group, $filePath);
            
            return response()->json($report);
            
        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
            return response()->json([
                'message' => 'Failed to read the spreadsheet. Please ensure it is a valid Excel file.',
                'errors' => ['file' => ['Invalid spreadsheet format.']]
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while processing the file: ' . $e->getMessage(),
                'errors' => ['file' => [$e->getMessage()]]
            ], 422);
        }
    }

    public function syncDefaultRates(Group $group)
    {
        if ($group->airports()->count() > 0) {
            return response()->json(['airports' => []]);
        }

        $hotels = $group->hotels()->with('hotel.hotelAirportRates.airport')->get();


        $hotelRates = $hotels->pluck('hotel.hotelAirportRates')->flatten()->whereNotNull('airport_id')->unique('airport_id');

        $isFirst = true;
        $hotelRates->each(function($rate) use ($group, &$isFirst) {
            $group->airports()->create([
                'airport_id' => $rate->airport_id,
                'transfer_id' => $rate->airport->transfer_id,
                'transportation_rate' => $rate->transportation_rate,
                'single_transportation_rate' => $rate->single_transportation_rate,
                'one_way_transportation_rate' => $rate->one_way_transportation_rate,
                'default' => $isFirst,
            ]);
            $isFirst = false;
        });

        return response()->json(['airports' => $group->load('airports.airport', 'airports.transfer')->airports]);
    }
}
