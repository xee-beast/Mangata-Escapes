<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Group;
use App\Services\CalendarEventService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\CalendarEvent;
use Carbon\Carbon;

class CalendarController extends Controller
{
    public function index(CalendarEventService $service, Request $request) {
        $year = $request->input('year');

        $groups = Group::with([
                'due_dates' => function ($q) use ($year) {
                    $q->select('id', 'date', 'group_id', 'amount', 'type')
                        ->whereYear('date', $year);
                },
                'groupAttritionDueDates' => function ($q) use ($year) {
                    $q->select('id', 'date', 'group_id')
                        ->whereYear('date', $year);
                },
                'paymentArrangements' => function ($q) use ($year) {
                    $q->whereYear('due_date', $year)
                        ->with('booking:id,order');
                }
            ])
            ->select('id', 'bride_first_name', 'bride_last_name', 'groom_first_name', 'groom_last_name', 'cancellation_date', 'balance_due_date', 'event_date', 'transportation_submit_before', 'email', 'id_at_provider')
            ->where(function ($query) use ($year) {
                $query->whereYear('event_date', $year)
                    ->orWhereYear('cancellation_date', $year)
                    ->orWhereYear('balance_due_date', $year)
                    ->orWhereYear('transportation_submit_before', $year)
                    ->orWhereHas('due_dates', function ($q) use ($year) {
                        $q->whereYear('date', $year);
                    })
                    ->orWhereHas('groupAttritionDueDates', function ($q) use ($year) {
                        $q->whereYear('date', $year);
                    })
                    ->orWhereHas('paymentArrangements', function ($q) use ($year) {
                        $q->whereYear('due_date', $year);
                    });
            });

        $bookings = Booking::with([
                'bookingDueDates' => function ($q) use ($year) {
                    $q->select('id', 'date', 'booking_id', 'amount', 'type')
                        ->whereYear('date', $year);
                },
                'paymentArrangements' => function ($q) use ($year) {
                    $q->whereYear('due_date', $year);
                }
            ])
            ->select('id', 'order', 'reservation_leader_first_name', 'reservation_leader_last_name', 'cancellation_date', 'balance_due_date', 'check_in', 'check_out', 'transportation_submit_before', 'email', 'id_at_provider')
            ->whereNull('group_id')
            ->where(function ($query) use ($year) {
                $query->whereYear('check_in', $year)
                    ->orWhereYear('check_out', $year)
                    ->orWhereYear('cancellation_date', $year)
                    ->orWhereYear('balance_due_date', $year)
                    ->orWhereYear('transportation_submit_before', $year)
                    ->orWhereHas('bookingDueDates', function ($q) use ($year) {
                        $q->whereYear('date', $year);
                    })
                    ->orWhereHas('paymentArrangements', function ($q) use ($year) {
                        $q->whereYear('due_date', $year);
                    });
            });

        $calendar_events = Event::with('booking:id,group_id', 'calendarEvent')
            ->where(function ($query) use ($year) {
                $query->whereYear('start_date', $year)
                    ->orWhereYear('end_date', $year);
            });

        if ($request->filled('search')) {
            $keyword = '%' . $request->input('search') . '%';

            $groups->where(function ($q) use ($keyword, $request) {
                $q->whereRaw("CONCAT(bride_first_name, ' ', bride_last_name) LIKE ?", [$keyword])
                    ->orWhereRaw("CONCAT(groom_first_name, ' ', groom_last_name) LIKE ?", [$keyword])
                    ->orWhereRaw("CONCAT(bride_first_name, ' ', bride_last_name, ' & ', groom_first_name, ' ', groom_last_name) LIKE ?", [$keyword])
                    ->orWhereRaw("CONCAT(bride_first_name, ' ', bride_last_name, ' ', groom_first_name, ' ', groom_last_name) LIKE ?", [$keyword])
                    ->orWhereRaw("CONCAT(bride_last_name, ' & ', groom_last_name) LIKE ?", [$keyword])
                    ->orWhereRaw("CONCAT(bride_last_name, ' ', groom_last_name) LIKE ?", [$keyword])
                    ->orWhereRaw("CONCAT(bride_first_name, ' & ', groom_first_name) LIKE ?", [$keyword])
                    ->orWhereRaw("CONCAT(bride_first_name, ' ', groom_first_name) LIKE ?", [$keyword])
                    ->orWhere('email', $request->input('search'))
                    ->orWhere('id_at_provider', $request->input('search'));
            });

            $bookings->where(function ($q) use ($keyword, $request) {
                $q->whereRaw("CONCAT(reservation_leader_first_name, ' ', reservation_leader_last_name) LIKE ?", [$keyword])
                    ->orWhere('email', $request->input('search'))
                    ->orWhere('id_at_provider', $request->input('search'));
            });

            $calendar_events->where(function ($query) use ($keyword, $request) {
                $query->where('title', 'LIKE', $keyword)
                    ->orWhereHas('booking.group', function ($q) use ($keyword, $request) {
                        $q->whereRaw("CONCAT(bride_first_name, ' ', bride_last_name) LIKE ?", [$keyword])
                            ->orWhereRaw("CONCAT(groom_first_name, ' ', groom_last_name) LIKE ?", [$keyword])
                            ->orWhereRaw("CONCAT(bride_first_name, ' ', bride_last_name, ' & ', groom_first_name, ' ', groom_last_name) LIKE ?", [$keyword])
                            ->orWhereRaw("CONCAT(bride_first_name, ' ', bride_last_name, ' ', groom_first_name, ' ', groom_last_name) LIKE ?", [$keyword])
                            ->orWhereRaw("CONCAT(bride_last_name, ' & ', groom_last_name) LIKE ?", [$keyword])
                            ->orWhereRaw("CONCAT(bride_last_name, ' ', groom_last_name) LIKE ?", [$keyword])
                            ->orWhereRaw("CONCAT(bride_first_name, ' & ', groom_first_name) LIKE ?", [$keyword])
                            ->orWhereRaw("CONCAT(bride_first_name, ' ', groom_first_name) LIKE ?", [$keyword])
                        ->orWhere('email', $request->input('search'))
                        ->orWhere('id_at_provider', $request->input('search'));
                    })
                    ->orWhereHas('booking', function ($q) use ($keyword, $request) {
                        $q->whereRaw("CONCAT(reservation_leader_first_name, ' ', reservation_leader_last_name) LIKE ?", [$keyword])
                            ->orWhere('email', $request->input('search'))
                            ->orWhere('id_at_provider', $request->input('search'));
                    });
            });
        }

        if ($request->calendar_events_filter) {
            $calendar_events = $calendar_events->whereIn('calendar_event_id', explode(',' ,$request->calendar_events_filter));
        }

        $groups = $groups->get();
        $bookings = $bookings->get();
        $calendar_events = $calendar_events->get();

        $events = $service->getEvents($groups, $bookings, $calendar_events, $request->calendar_events_filter, $year);

        return response()->json($events);
    }

    public function bookings(Request $request)
    {
        $search = $request->input('search');

        $query = Booking::join('booking_clients', 'bookings.id', '=', 'booking_clients.booking_id')
            ->select(
                'bookings.id',
                DB::raw('GROUP_CONCAT(DISTINCT booking_clients.reservation_code SEPARATOR ",") as reservation_codes'),
                DB::raw('GROUP_CONCAT(CONCAT(booking_clients.first_name, " ", booking_clients.last_name) SEPARATOR ", ") as names'),
                DB::raw('CONCAT(GROUP_CONCAT(DISTINCT booking_clients.reservation_code SEPARATOR " , "), " - ", GROUP_CONCAT(CONCAT(booking_clients.first_name, " ", booking_clients.last_name) SEPARATOR ", ")) as label')
            );

        if ($search) {
            $query = $query->where(function($q) use ($search) {
                $q->where('booking_clients.first_name', 'like', "%{$search}%")
                    ->orWhere('booking_clients.last_name', 'like', "%{$search}%")
                    ->orWhere('booking_clients.reservation_code', 'like', "%{$search}%");
            });
        }

        if($request->input('booking_id') !='null' && $request->input('booking_id') !=null &&  $request->input('booking_id') != ""){
            $query = $query->orWhere('bookings.id',$request->input('booking_id'));
        }

        $bookings = $query->groupBy('bookings.id')->paginate(20);

        return response()->json($bookings);
    }

    public function store(Request $request) {
        $request->validate([
            'title' => 'required|max:255',
            'description' => 'nullable|max:3000',
            'start_date' => 'required|date',
            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date',
                function ($attribute, $value, $fail) use ($request) {
                    $start = Carbon::parse($request->start_date);
                    $end = Carbon::parse($value);
                    if ($start->diffInDays($end) > 14) {
                        $fail('The end date must not be more than 2 weeks after the start date.');
                    }
                }
            ],
            'booking_id' => 'nullable',
            'calendar_event_id' => 'required',
        ], [], [
            'calendar_event_id' => 'calendar event type'
        ]);

        $event = Event::create([
            'title' => $request->title,
            'description' => $request->description,
            'start_date' => Carbon::parse($request->start_date)->format('Y-m-d'),
            'end_date' => Carbon::parse($request->end_date)->format('Y-m-d'),
            'booking_id' => $request->booking_id,
            'calendar_event_id' => $request->calendar_event_id,
        ]);

        return response()->json($event);

    }

    public function update(Request $request) {
        $request->validate([
            'event_id' => 'required',
            'title' => 'required|max:255',
            'description' => 'nullable|max:3000',
            'start_date' => 'required|date',
            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date',
                function ($attribute, $value, $fail) use ($request) {
                    $start = Carbon::parse($request->start_date);
                    $end = Carbon::parse($value);
                    if ($start->diffInDays($end) > 14) {
                        $fail('The end date must not be more than 2 weeks after the start date.');
                    }
                }
            ],
            'booking_id' => 'nullable',
            'calendar_event_id' => 'required',
        ], [], [
            'calendar_event_id' => 'calendar event type'
        ]);

        $event = Event::where('id', $request->event_id)->update([
            'title' => $request->title,
            'description' => $request->description,
            'start_date' => Carbon::parse($request->start_date)->format('Y-m-d'),
            'end_date' => Carbon::parse($request->end_date)->format('Y-m-d'),
            'booking_id' => $request->booking_id,
            'calendar_event_id' => $request->calendar_event_id,
        ]);

        return response()->json($event);
    }

    public function delete($event_id) {
        Event::where('id', $event_id)->delete();
    }

    public function getAllEvents(Request $request)
    {
        $search = $request->input('search');
        $calendarEvents = CalendarEvent::query();

        if ($search) {
            $calendarEvents = $calendarEvents->where('name', 'like', "%{$search}%");
        }

        $calendarEvents = CalendarEvent::get();

        return response()->json($calendarEvents);
    }
}
