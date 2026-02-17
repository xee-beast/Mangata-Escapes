<?php

namespace App\Http\Controllers\Couples;

use App\Http\Controllers\Controller;
use App\Models\Group;
use PDF;
use Carbon\Carbon;
use App\Models\Airline;
use App\Http\Resources\ImageResource;

class PageController extends Controller
{
    /**
     * Display the groups personal couple page.
     *
     * @param \App\Models\Group $group
     * @return \Illuminate\Http\Response
     */
    public function index(Group $group)
    {
        // TODO: remove later once this group has traveled
        if ($group->slug === "teresamichael") {
            return redirect()->away('https://www.barefoot-bridal.com/teresamichael');
        }

        if($group->trashed() || $group->event_date <= Carbon::now()) abort(404);

        $countries = \App\Models\Country::whereHas('states')->with('states')->get();
        $group->load(['hotels' => function($query) { $query->with('rooms')->whereHas('rooms'); }, 'airports.airport', 'groupFaqs']);

        $bookingForm = (object)[
            'group' => [
                'id' => $group->id,
                'is_fit' => $group->is_fit,
                'name' => $group->name,
                'date' => $group->event_date->format('m/d/Y'),
                'isNonRefundable' => $group->cancellation_date->isBefore(today()),
                'deposit' => $group->deposit,
                'depositType' => $group->deposit_type,
                'supplierName' => $group->provider ? $group->provider->name : '',
                'hasTransportation' => $group->transportation,
                'transportation' => $group->transportation_rate,
                'transportationType' => ucwords($group->transportation_type),
                'termsConditionsUrl' => route('termsConditions', ['group' => $group->slug]),
                'insuranceUrl' => $group->getInsuranceRate()->url,
                'cancellationDate' => $group->cancellation_date->format('m/d/Y'),
                'balance_due_date' => $group->balance_due_date->format('m/d/Y'),
                'groupsEmail' => config('emails.groups'),
                'dueDates' => $group->due_dates->map(function($dueDate) {
                    return [
                        'date' => $dueDate->date->format('m/d/Y'),
                        'type' => $dueDate->type,
                        'amount' => $dueDate->amount,
                    ];
                }),
                'hotels' => $group->hotels->map(function($hotel_block) {
                    return [
                        'id' => $hotel_block->id,
                        'name' => $hotel_block->hotel->name,
                        'description' => $hotel_block->hotel->description,
                        'images' => ImageResource::collection($hotel_block->hotel->images),
                    ];
                }),
                'minNights' => $group->min_nights,
                'disableInvoiceSplitting' => $group->disable_invoice_splitting,
                'acceptsNewBookings' => $group->accepts_new_bookings,
            ],
            'hotels' => $group->hotels->map(function ($hotel_block) {
                return [
                    'id' => $hotel_block->id,
                    'name' => $hotel_block->hotel->name,
                    'rooms' => $hotel_block->rooms->reject(function ($room) {
                        return $room->sold_out;
                    })->map(function ($room_block) {
                        return [
                            'id' => $room_block->id,
                            'name' => $room_block->room->name,
                            'beds' => $room_block->room->beds,
                            'adultsOnly' => $room_block->room->adults_only,
                            'maxGuests' => $room_block->room->max_occupants,
                            'maxAdults' => $room_block->room->max_adults,
                            'maxChildren' => $room_block->room->max_children,
                            'minAdultsPerChild' => $room_block->min_adults_per_child,
                            'maxChildrenPerAdult' => $room_block->max_children_per_adult
                        ];
                    })->values()
                ];
            })
        ];

        $cardForm = (object)[
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
                'termsConditionsUrl' => route('termsConditions', ['group' => $group->slug]),
                'insuranceUrl' => $group->getInsuranceRate()->url,
                'cancellationDate' => $group->cancellation_date->format('m/d/Y'),
                'balance_due_date' => $group->balance_due_date->format('m/d/Y'),
                'groupsEmail' => config('emails.groups'),
                'dueDates' => $group->due_dates->map(function($dueDate) {
                    return [
                        'date' => $dueDate->date->format('m/d/Y'),
                        'type' => $dueDate->type,
                        'amount' => $dueDate->amount,
                    ];
                })
            ]
        ];

        $paymentForm = (object)[
            'group' => [
                'id' => $group->id,
                'supplierName' => $group->provider ? $group->provider->name : '',
                'name' => $group->name,
                'termsConditionsUrl' => route('termsConditions', ['group' => $group->slug]),
                'insuranceUrl' => $group->getInsuranceRate()->url,
                'cancellationDate' => $group->cancellation_date->format('m/d/Y'),
                'balance_due_date' => $group->balance_due_date->format('m/d/Y'),
                'groupsEmail' => config('emails.groups'),
                'dueDates' => $group->due_dates->map(function($dueDate) {
                    return [
                        'date' => $dueDate->date->format('m/d/Y'),
                        'type' => $dueDate->type,
                        'amount' => $dueDate->amount,
                    ];
                })
            ]
        ];

        $invoiceForm = (object)[
            'group' => [
                'id' => $group->id,
                'slug' => $group->slug,
                'name' => $group->name,
            ]
        ];

        $flightManifestForm = (object)[
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
                'date' => $group->event_date->format('m/d/Y'),
                'groupsEmail' => config('emails.groups'),
                'airports' => $group->airports->map(function($airport) {
                    return [
                        'value' => $airport->airport->id,
                        'text' => $airport->airport->airport_code,
                    ];
                }),
            ]
        ];

        $airlines = Airline::orderBy('name', 'asc')->get();

        $roomBlocks = $group->hotels
            ->filter(fn($hotel_block) => $hotel_block->rooms->where('is_active', true)->isNotEmpty())
            ->flatMap(function($hotel_block) {
                return $hotel_block->sorted_rooms->filter(fn($room_block) => $room_block->is_active);
            });

        $sortedRoomBlocks = $roomBlocks->sortBy(function ($room) {
            return $room->sold_out ? PHP_INT_MAX : 
                  ($room->rates->max('rate') ?? PHP_INT_MAX);
        });

        return view('web.couples.index', [
            'group' => $group,
            'countries' => $countries,
            'bookingForm' => $bookingForm,
            'cardForm' => $cardForm,
            'paymentForm' => $paymentForm,
            'invoiceForm' => $invoiceForm,
            'flightManifestForm' => $flightManifestForm,
            'airlines' => $airlines,
            'sortedRoomBlocks' => $sortedRoomBlocks,
        ]);
    }

    /**
     * Display the groups booking terms & conditions.
     *
     * @param \App\Models\Group $group
     * @return PDF
     */
    public function termsConditions(Group $group)
    {
        $pdf = PDF::loadView('pdf.termsConditions', ['group' => $group])->stream('Terms-Conditions.pdf');

        $pdf->headers->set('X-Vapor-Base64-Encode', 'True');

        return $pdf;
    }
}
