<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\ResultsResource;
use Illuminate\Http\Request;

class ResultsController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search', '');

        $query = Booking::withTrashed()
            ->with(['guests', 'group', 'booking_clients.client'])
            ->where(function($q) {
                $q->where(function($individualQuery) {
                    $individualQuery->whereNull('group_id')
                                   ->where('check_in', '>=', now());
                })
                ->orWhereHas('group', function($groupQuery) {
                    $groupQuery->whereNull('deleted_at')
                               ->where('event_date', '>=', now());
                });
            });

        if($search != '') {
            $query->where(function($q) use ($search) {
                $q->whereHas('guests', function($guestQuery) use ($search) {
                    $guestQuery->where(DB::raw("CONCAT(guests.first_name, ' ', guests.last_name)"), 'LIKE', '%' . $search . '%');
                })
                ->orWhereHas('booking_clients', function($bookingClientQuery) use ($search) {
                    $bookingClientQuery->where(function($bcQuery) use ($search) {
                        $bcQuery->where(DB::raw("CONCAT(booking_clients.first_name, ' ', booking_clients.last_name)"), 'LIKE', '%' . $search . '%')
                                ->orWhere('telephone', 'LIKE', '%' . $search . '%');
                    })
                    ->orWhereHas('client', function($clientQuery) use ($search) {
                        $clientQuery->where(function($cQuery) use ($search) {
                            $cQuery->where(DB::raw("CONCAT(clients.first_name, ' ', clients.last_name)"), 'LIKE', '%' . $search . '%')
                                   ->orWhere('email', 'LIKE', '%' . $search . '%')
                                   ->orWhere('telephone', 'LIKE', '%' . $search . '%')
                                   ->orWhere('reservation_code', $search);
                        });
                    });
                })
                ->orWhere(function($bookingQuery) use ($search) {
                    if (str_contains($search, '/')) {
                        [$groupId, $order] = array_map('trim', explode('/', $search, 2));

                        $bookingQuery->whereHas('group', function($q) use ($groupId) {
                            $q->where('id_at_provider', $groupId);
                        })->where('order', $order);
                    } else {
                        $bookingQuery->where('booking_id', $search);
                    }
                });
            });
        }

        $query->orderBy('created_at', 'DESC');

        return ResultsResource::collection($query->paginate($request->query('paginate', 25)));
    }
}