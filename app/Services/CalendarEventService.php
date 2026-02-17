<?php

namespace App\Services;

use App\Models\CalendarEvent;
use Carbon\Carbon;

class CalendarEventService
{
    const DEFAULT_COLOR = '#D1B7B7';

    public function getEvents($groups, $bookings, $calendar_events, $calendar_events_filter, $year)
    {
        $events = [];
        $event_types = CalendarEvent::where('is_default', 1);

        if ($calendar_events_filter) {
            $event_types = $event_types->whereIn('id', explode(',' ,$calendar_events_filter));
        }

        $event_types = $event_types->get();

        foreach ($calendar_events as $calendar_event) {
            $events[] = $this->makeEvent(
                $calendar_event->id,
                $calendar_event->booking_id,
                $calendar_event->booking ? $calendar_event->booking->group_id : null,
                $calendar_event->title,
                $calendar_event->description,
                null,
                $calendar_event->start_date,
                $calendar_event->end_date,
                $calendar_event->calendarEvent ? $calendar_event->calendarEvent->color : self::DEFAULT_COLOR,
                $calendar_event->calendarEvent ? $calendar_event->calendarEvent->color : self::DEFAULT_COLOR,
                $calendar_event->calendarEvent ? $calendar_event->calendarEvent->color : self::DEFAULT_COLOR,
                $calendar_event->calendarEvent ? $calendar_event->calendarEvent->id : null,
            );
        }

        foreach ($groups as $group) {
            $title = "{$group->bride_last_name} & {$group->groom_last_name}";

            if ($group->transportation_submit_before) {
                $transportation_submit_before_event_type = $event_types->where('name', 'Submit Flight Itinerary Before Date')->first();

                if ($transportation_submit_before_event_type && Carbon::parse($group->transportation_submit_before)->year == $year) {
                    $events[] = $this->makeEvent(
                        null,
                        null,
                        $group->id,
                        "Group Flight Itinerary Submission Due Date - {$title}",
                        null,
                        null,
                        $group->transportation_submit_before,
                        $group->transportation_submit_before,
                        $transportation_submit_before_event_type ? $transportation_submit_before_event_type->color : self::DEFAULT_COLOR,
                        $transportation_submit_before_event_type ? $transportation_submit_before_event_type->color : self::DEFAULT_COLOR,
                        $transportation_submit_before_event_type ? $transportation_submit_before_event_type->color : self::DEFAULT_COLOR,
                        $transportation_submit_before_event_type ? $transportation_submit_before_event_type->id : null,
                    );
                }
            }

            if ($group->event_date) {
                $event_date_event_type = $event_types->where('name', 'Event Date')->first();

                if($event_date_event_type && Carbon::parse($group->event_date)->year == $year) {
                    $events[] = $this->makeEvent(
                        null,
                        null,
                        $group->id,
                        "Group Event Date - {$title}",
                        null,
                        null,
                        $group->event_date,
                        $group->event_date,
                        $event_date_event_type ? $event_date_event_type->color : self::DEFAULT_COLOR,
                        $event_date_event_type ? $event_date_event_type->color : self::DEFAULT_COLOR,
                        $event_date_event_type ? $event_date_event_type->color : self::DEFAULT_COLOR,
                        $event_date_event_type ? $event_date_event_type->id : null,
                    );
                }
            }

            if ($group->cancellation_date) {
                $cancellation_event_type = $event_types->where('name', 'Cancellation Date')->first();

                if ($cancellation_event_type && Carbon::parse($group->cancellation_date)->year == $year) {
                    $events[] = $this->makeEvent(
                        null,
                        null,
                        $group->id,
                        "Group Cancellation Date - {$title}",
                        null,
                        null,
                        $group->cancellation_date,
                        $group->cancellation_date,
                        $cancellation_event_type ? $cancellation_event_type->color : self::DEFAULT_COLOR,
                        $cancellation_event_type ? $cancellation_event_type->color : self::DEFAULT_COLOR,
                        $cancellation_event_type ? $cancellation_event_type->color : self::DEFAULT_COLOR,
                        $cancellation_event_type ? $cancellation_event_type->id : null,
                    );
                }
            }

            if ($group->balance_due_date) {
                $balance_due_date_event_type = $event_types->where('name', 'Balance Due Date')->first();

                if ($balance_due_date_event_type && Carbon::parse($group->balance_due_date)->year == $year) {
                    $events[] = $this->makeEvent(
                        null,
                        null,
                        $group->id,
                        "Group Balance Due Date - {$title}",
                        null,
                        null,
                        $group->balance_due_date,
                        $group->balance_due_date,
                        $balance_due_date_event_type ? $balance_due_date_event_type->color : self::DEFAULT_COLOR,
                        $balance_due_date_event_type ? $balance_due_date_event_type->color : self::DEFAULT_COLOR,
                        $balance_due_date_event_type ? $balance_due_date_event_type->color : self::DEFAULT_COLOR,
                        $balance_due_date_event_type ? $balance_due_date_event_type->id : null,
                    );
                }
            }

            if ($group->due_dates) {
                foreach ($group->due_dates as $due_date) {
                    $due_date_event_type = $event_types->where('name', 'Due Date')->first();

                    if ($due_date_event_type) {
                        $events[] = $this->makeEvent(
                            null,
                            null,
                            $group->id,
                            "Group Due Date - {$title}",
                            null,
                            "{$due_date->amount} " . ($due_date->type == 'percentage' ? '%' : ($due_date->type == 'price' ? '$' : $due_date->type)),
                            $due_date->date,
                            $due_date->date,
                            $due_date_event_type ? $due_date_event_type->color : self::DEFAULT_COLOR,
                            $due_date_event_type ? $due_date_event_type->color : self::DEFAULT_COLOR,
                            $due_date_event_type ? $due_date_event_type->color : self::DEFAULT_COLOR,
                            $due_date_event_type ? $due_date_event_type->id : null,
                        );
                    }
                }
            }

            if ($group->groupAttritionDueDates) {
                foreach ($group->groupAttritionDueDates as $due_date) {
                    $attrition_due_date_event_type = $event_types->where('name', 'Attrition Due Date')->first();

                    if ($attrition_due_date_event_type) {
                        $events[] = $this->makeEvent(
                            null,
                            null,
                            $group->id,
                            "Group Attrition Due Date - {$title}",
                            null,
                            null,
                            $due_date->date,
                            $due_date->date,
                            $attrition_due_date_event_type ? $attrition_due_date_event_type->color : self::DEFAULT_COLOR,
                            $attrition_due_date_event_type ? $attrition_due_date_event_type->color : self::DEFAULT_COLOR,
                            $attrition_due_date_event_type ? $attrition_due_date_event_type->color : self::DEFAULT_COLOR,
                            $attrition_due_date_event_type ? $attrition_due_date_event_type->id : null,
                        );
                    }
                }
            }

            if ($group->paymentArrangements) {
                foreach ($group->paymentArrangements as $paymentArrangement) {
                    $payment_arrangement_event_type = $event_types->where('name', 'Booking Payment Arrangement Due Date')->first();

                    if ($payment_arrangement_event_type) {
                        $amount = intval($paymentArrangement->amount);

                        $events[] = $this->makeEvent(
                            null,
                            $paymentArrangement->booking_id,
                            $group->id,
                            $paymentArrangement->booking ? "Room #{$paymentArrangement->booking->order} - Payment Arrangement Due Date - {$title}" : "Group Payment Arrangement Due Date - {$title}",
                            null,
                            "{$amount} $",
                            $paymentArrangement->due_date,
                            $paymentArrangement->due_date,
                            $payment_arrangement_event_type ? $payment_arrangement_event_type->color : self::DEFAULT_COLOR,
                            $payment_arrangement_event_type ? $payment_arrangement_event_type->color : self::DEFAULT_COLOR,
                            $payment_arrangement_event_type ? $payment_arrangement_event_type->color : self::DEFAULT_COLOR,
                            $payment_arrangement_event_type ? $payment_arrangement_event_type->id : null,
                        );
                    }
                }
            }
        }

        foreach ($bookings as $booking) {
            $title = "{$booking->reservation_leader_first_name} {$booking->reservation_leader_last_name}";

            if ($booking->transportation_submit_before) {
                $transportation_submit_before_event_type = $event_types->where('name', 'Submit Flight Itinerary Before Date')->first();

                if ($transportation_submit_before_event_type && Carbon::parse($booking->transportation_submit_before)->year == $year) {
                    $events[] = $this->makeEvent(
                        null,
                        $booking->id,
                        null,
                        "Individual Booking Flight Itinerary Submission Due Date - {$title}",
                        null,
                        null,
                        $booking->transportation_submit_before,
                        $booking->transportation_submit_before,
                        $transportation_submit_before_event_type ? $transportation_submit_before_event_type->color : self::DEFAULT_COLOR,
                        $transportation_submit_before_event_type ? $transportation_submit_before_event_type->color : self::DEFAULT_COLOR,
                        $transportation_submit_before_event_type ? $transportation_submit_before_event_type->color : self::DEFAULT_COLOR,
                        $transportation_submit_before_event_type ? $transportation_submit_before_event_type->id : null,
                    );
                }
            }

            if ($booking->check_in && $booking->check_out) {
                $event_date_event_type = $event_types->where('name', 'Event Date')->first();

                if($event_date_event_type && (Carbon::parse($booking->check_in)->year == $year || Carbon::parse($booking->check_out)->year == $year)) {
                    $events[] = $this->makeEvent(
                        null,
                        $booking->id,
                        null,
                        "Individual Booking Dates - {$title}",
                        null,
                        null,
                        $booking->check_in,
                        $booking->check_out,
                        $event_date_event_type ? $event_date_event_type->color : self::DEFAULT_COLOR,
                        $event_date_event_type ? $event_date_event_type->color : self::DEFAULT_COLOR,
                        $event_date_event_type ? $event_date_event_type->color : self::DEFAULT_COLOR,
                        $event_date_event_type ? $event_date_event_type->id : null,
                    );
                }
            }

            if ($booking->cancellation_date) {
                $cancellation_event_type = $event_types->where('name', 'Cancellation Date')->first();

                if ($cancellation_event_type && Carbon::parse($booking->cancellation_date)->year == $year) {
                    $events[] = $this->makeEvent(
                        null,
                        $booking->id,
                        null,
                        "Individual Booking Cancellation Date - {$title}",
                        null,
                        null,
                        $booking->cancellation_date,
                        $booking->cancellation_date,
                        $cancellation_event_type ? $cancellation_event_type->color : self::DEFAULT_COLOR,
                        $cancellation_event_type ? $cancellation_event_type->color : self::DEFAULT_COLOR,
                        $cancellation_event_type ? $cancellation_event_type->color : self::DEFAULT_COLOR,
                        $cancellation_event_type ? $cancellation_event_type->id : null,
                    );
                }
            }

            if ($booking->balance_due_date) {
                $balance_due_date_event_type = $event_types->where('name', 'Balance Due Date')->first();

                if ($balance_due_date_event_type && Carbon::parse($booking->balance_due_date)->year == $year) {
                    $events[] = $this->makeEvent(
                        null,
                        $booking->id,
                        null,
                        "Individual Booking Balance Due Date - {$title}",
                        null,
                        null,
                        $booking->balance_due_date,
                        $booking->balance_due_date,
                        $balance_due_date_event_type ? $balance_due_date_event_type->color : self::DEFAULT_COLOR,
                        $balance_due_date_event_type ? $balance_due_date_event_type->color : self::DEFAULT_COLOR,
                        $balance_due_date_event_type ? $balance_due_date_event_type->color : self::DEFAULT_COLOR,
                        $balance_due_date_event_type ? $balance_due_date_event_type->id : null,
                    );
                }
            }

            if ($booking->bookingDueDates) {
                foreach ($booking->bookingDueDates as $due_date) {
                    $due_date_event_type = $event_types->where('name', 'Due Date')->first();

                    if ($due_date_event_type) {
                        $events[] = $this->makeEvent(
                            null,
                            $booking->id,
                            null,
                            "Individual Booking Due Date - {$title}",
                            null,
                            "{$due_date->amount} " . ($due_date->type == 'percentage' ? '%' : ($due_date->type == 'price' ? '$' : $due_date->type)),
                            $due_date->date,
                            $due_date->date,
                            $due_date_event_type ? $due_date_event_type->color : self::DEFAULT_COLOR,
                            $due_date_event_type ? $due_date_event_type->color : self::DEFAULT_COLOR,
                            $due_date_event_type ? $due_date_event_type->color : self::DEFAULT_COLOR,
                            $due_date_event_type ? $due_date_event_type->id : null,
                        );
                    }
                }
            }

            if ($booking->paymentArrangements) {
                foreach ($booking->paymentArrangements as $paymentArrangement) {
                    $payment_arrangement_event_type = $event_types->where('name', 'Booking Payment Arrangement Due Date')->first();

                    if ($payment_arrangement_event_type) {
                        $amount = intval($paymentArrangement->amount);

                        $events[] = $this->makeEvent(
                            null,
                            $booking->id,
                            null,
                            "Individual Booking #{$booking->order} - Payment Arrangement Due Date - {$title}",
                            null,
                            "{$amount} $",
                            $paymentArrangement->due_date,
                            $paymentArrangement->due_date,
                            $payment_arrangement_event_type ? $payment_arrangement_event_type->color : self::DEFAULT_COLOR,
                            $payment_arrangement_event_type ? $payment_arrangement_event_type->color : self::DEFAULT_COLOR,
                            $payment_arrangement_event_type ? $payment_arrangement_event_type->color : self::DEFAULT_COLOR,
                            $payment_arrangement_event_type ? $payment_arrangement_event_type->id : null,
                        );
                    }
                }
            }
        }

        return $events;
    }

    public function makeEvent($event_id, $booking_id, $group_id, $title, $description, $due_amount, $start, $end, $color_value, $backgroundColor, $borderColor, $calendarEventType) {
        return [
            'event_id' => $event_id,
            'booking_id' => $booking_id,
            'group_id' => $group_id,
            'title' => $title,
            'description' => $description,
            'due_amount' => $due_amount,
            'start' => $start ? Carbon::parse($start)->format('Y-m-d') : null,
            'end' => $end ? Carbon::parse($end)->addDay()->format('Y-m-d') : null,
            'color_value' => $color_value,
            'backgroundColor' => $backgroundColor,
            'borderColor' => $borderColor,
            'calendar_event_type' => $calendarEventType
        ];
    }
}
