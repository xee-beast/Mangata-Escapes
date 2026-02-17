<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CalendarEvent;
use App\Http\Resources\CalendarEventResource;

class CalendarEventController extends Controller
{

    public function index(Request $request)
    {
        $events = CalendarEvent::query();

        $search = $request->query('search', '');
        
        if (!empty($search)) {
            $events = $events->where('name', 'like', "%{$search}%");
        }
        
        $events->orderBy('id', 'desc');

        return CalendarEventResource::collection($events->paginate($request->query('paginate', 10)))
                ->additional([
                'can' => [
                    'create' => $request->user()->can('manage event types'),
                ]
            ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'event_name' => 'required|unique:calendar_events,name|max:255',
            'color' => 'required|string|max:255',
        ]);

        $calendar_event = new CalendarEvent();
        $calendar_event->name = $request->input('event_name');
        $calendar_event->color = $request->input('color');
        $calendar_event->is_default = 0;
        $calendar_event->save();
        
        return (new CalendarEventResource($calendar_event))->response()->setStatusCode(201);
    }

    public function update(Request $request, CalendarEvent $calendar_event)
    {
        $request->validate([
            'event_name' => 'required|unique:calendar_events,name,' . $calendar_event->id . '|max:255',
            'color' => 'required|string|max:255',
        ]);

        if ($calendar_event->is_default && $calendar_event->name !== $request->input('event_name')) {
            return response()->json([
               'message' => 'Default events cannot be renamed.'
            ], 422);
        }

        $calendar_event->name = $request->input('event_name');
        $calendar_event->color = $request->input('color');
        $calendar_event->save();

        return (new CalendarEventResource($calendar_event));
    }

    public function destroy(CalendarEvent $calendar_event)
    {
        if ($calendar_event->is_default) {
            return response()->json([
                'message' => 'This is a default event and it cannot be deleted.'
            ], 422);
        } else {
            $calendar_event->delete();

            return response()->json()->setStatusCode(204);
        }
    }
}
