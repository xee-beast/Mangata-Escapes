<?php

namespace App\Http\Controllers;

use App\Http\Resources\BookingResource;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Http\Request;

class UnpaidBookingController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Booking::class, 'booking');
    }

    public function index(Request $request)
    {
        $bookings = Booking::leftJoin('groups', 'bookings.group_id', '=', 'groups.id')
            ->where('is_paid', false)
            ->where(function ($query) {
                $query->whereHas('group', function ($q) {
                        $q->whereNull('deleted_at')
                            ->whereDate('groups.balance_due_date', '<', Carbon::today())
                            ->whereDate('event_date', '>=', Carbon::today());
                    })
                    ->orWhere(function ($q) {
                        $q->whereNull('group_id')
                            ->whereDate('bookings.balance_due_date', '<', Carbon::today())
                            ->whereDate('check_in', '>=', Carbon::today());
                    });
            })
            ->orderByRaw('COALESCE(groups.event_date, bookings.check_in) asc')
            ->select('bookings.*')
            ->with([
                'group',
                'clients.guests',
                'clients.card',
                'clients.payments',
                'clients.pendingFitQuote',
                'clients.acceptedFitQuote',
                'clients.discardedFitQuote',
                'roomBlocks.hotel_block',
                'roomBlocks.rates',
                'roomBlocks.child_rates',
                'roomArrangements',
                'trackedChanges',
                'paymentArrangements',
            ]);
    
        return BookingResource::collection($bookings->paginate($request->query('paginate', 10)));
    }
}
