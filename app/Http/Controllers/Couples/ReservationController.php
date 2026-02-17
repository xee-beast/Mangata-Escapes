<?php

namespace App\Http\Controllers\Couples;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateReservation;
use App\Http\Requests\Couples\ViewReservation;
use App\Http\Requests\Couples\AddReservationClient;
use App\Models\Group;
use App\Models\Booking;
use App\Models\BookingRoomBlock;
use App\Models\GuestChange;
use App\Jobs\StageGuestReservation;
use App\Models\RoomBlock;
use App\Models\Guest;
use App\Models\TransportationType;
use App\Models\Client;
use App\Models\BookingClient;
use App\Notifications\ClientPaymentRequired;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Freelancehunt\Validators\CreditCard;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public function addSeperateClient(Group $group, AddReservationClient $request)
    {
        return response()->json();
    }

    public function showReservation(Group $group, ViewReservation $request)
    {
        $booking = Booking::whereHas('clients', function ($query) use ($request) {
            $query->where('reservation_code', $request->input('booking.code'))
                ->whereHas('client', function ($query) use ($request) {
                    $query->where('email', $request->input('booking.email'));
                });
            })
            ->with([
                'group.airports.airport',
                'clients.guests',
                'clients.paymentArrangements',
                'roomBlocks.hotel_block.hotel',
                'clients.client',
                'paymentArrangements',
                'guestChanges',
                'trackedChanges'
            ])->first();

        if (!$booking) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }

        if (is_null($booking->confirmed_at)) {
            return response()->json([
                'message' => 'This feature will be available for this booking once it has been confirmed with the provider.<br> Please allow up to 3 business days to process your booking. We appreciate your patience.'
            ], 403);
        }

        $hasGuestChanges = $booking->guestChanges()
            ->whereNull('confirmed_at')
            ->whereNull('deleted_at')
            ->exists();

        $hasAdminChanges = $booking->trackedChanges()
            ->whereNull('confirmed_at')
            ->exists();

        if ($hasGuestChanges || $hasAdminChanges) {
            return response()->json([
                'message' => 'You currently have pending changes on this reservation. Please reach out to your Guest Experience Concierges at <a class="has-text-link" href="mailto:'.config('emails.groups').'">'.config('emails.groups').'</a> for additional assistance.',
            ], 403);
        }

        $roomArrangements = ($booking->roomBlocks ?? collect())->map(function ($roomBlock) {
            return [
                'hotel' => optional($roomBlock->hotel_block)->id,
                'hotelName' => optional(optional($roomBlock->hotel_block)->hotel)->name,
                'room' => $roomBlock->id,
                'roomName' => optional($roomBlock->room)->name,
                'bed' => $roomBlock->pivot->bed ?? null,
                'dates' => [
                    'start' => $roomBlock->pivot->check_in->format('Y-m-d') ?? null,
                    'end' => $roomBlock->pivot->check_out->format('Y-m-d') ?? null,
                ],
            ];
        })->values();

        $guests = $booking->clients->flatMap->guests->map(function ($guest) {
            return [
                'id' => $guest->id,
                'firstName' => $guest->first_name,
                'lastName' => $guest->last_name,
                'gender' => $guest->gender,
                'birthDate' => $guest->birth_date->format('Y-m-d'),
                'dates' => [
                    'start' => $guest->check_in->format('Y-m-d'),
                    'end' => $guest->check_out->format('Y-m-d'),
                ],
                'client' => $guest->booking_client_id,
                'insurance' => $guest->insurance,
                'transportation' => (bool) $guest->transportation,
                'transportation_type' => $guest->transportation_type,
                'deleted_at' => !!$guest->deleted_at,
                'customGroupAirport' => $guest->custom_group_airport,
            ];
        })->values();

        $customGroupAirports = $group->airports->map(function ($groupAirport) {
            return [
                'value' => $groupAirport->id,
                'text' => optional($groupAirport->airport)->airport_code,
            ];
        })->values();

        $hotels = $group->hotels->map(function ($hotelBlock) {
            $hotel = $hotelBlock->hotel;

            return [
                'value' => $hotelBlock->id,
                'text' => $hotel ? $hotel->name : null,
                'rooms' => $hotelBlock->rooms->map(function ($roomBlock) {
                    return [
                        'id' => $roomBlock->id,
                        'name' => $roomBlock->room->name,
                        'beds' => $roomBlock->room->beds ?? [],
                    ];
                })->values(),
            ];
        })->values();

        $clients = $booking->clients->map(function ($client) {
            return [
                'value' => $client->id,
                'text' => trim($client->first_name . ' ' . $client->last_name),
            ];
        })->values();

        $transportationTypes = TransportationType::all()->map(function ($type) {
            return [
                'value' => $type->id,
                'text' => $type->description,
            ];
        })->values();

        $currentClient = $booking->clients->where('reservation_code', $request->input('booking.code'))->first();

        return response()->json([
            'booking' => [
                'id' => $booking->id,
                'roomArrangements' => $roomArrangements,
                'specialRequests' => $booking->special_requests,
            ],
            'guests' => $guests,
            'group' => [
                'id' => $group->id,
                'eventDate' => $group->event_date,
                'transportation' => $group->transportation,
            ],
            'hotels' => $hotels,
            'clients' => $clients,
            'customGroupAirports' => $customGroupAirports,
            'transportationTypes' => $transportationTypes,
            'hasPaymentPlan' => $currentClient->paymentArrangements->count() > 0,
            'hasBalanceDueDatePassed' => now()->gt($booking->group->balance_due_date),
            'currentClientId' => $currentClient->id,
        ]);
    }

    public function updateReservation(Group $group, UpdateReservation $request)
    {
        $booking = Booking::withTrashed()->with(['paymentArrangements', 'payments', 'clients.client'])->find($request->input('booking.id'));
        $currentClient = $booking->clients()->where('reservation_code', $request->input('booking.code'))->first();

        if (!$booking) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }

        if ($booking->trashed()) {
            return response()->json(['message' => 'This booking has been deleted and cannot be updated.'], 400);
        }

        $duplicateGuests = collect();
        $duplicatesInRequest = collect();
        $guests = collect($request->validated()['guests']);

        foreach ($guests as $index => $guest) {
            $duplicatesInRequestIndex = $guests->filter(function($g) use ($guest) {
                return $g['firstName'] === $guest['firstName']
                    && $g['lastName'] === $guest['lastName']
                    && $g['birthDate'] === $guest['birthDate'];
            })->keys();

            if ($duplicatesInRequestIndex->count() > 1) {
                $duplicatesInRequest = $duplicatesInRequest->merge($duplicatesInRequestIndex);
            }

            $duplicateGuest = Guest::whereHas('booking_client.booking', function ($query) use ($group, $booking, $guest) {
                    $query->where('group_id', $group->id)->where('id', '!=', $booking->id);
                })
                ->where('first_name', $guest['firstName'])
                ->where('last_name', $guest['lastName'])
                ->where('birth_date', Carbon::parse($guest['birthDate'])->format('Y-m-d'))
                ->first();

            if ($duplicateGuest) {
                $duplicateGuests->push($index);
            }
        }

        if ($duplicateGuests->isNotEmpty() || $duplicatesInRequest->isNotEmpty()) {
            throw ValidationException::withMessages([
                'duplicate_guests_in_request' => $duplicatesInRequest->toArray(),
                'duplicate_guests' => $duplicateGuests->toArray(),
            ]);
        }

        $errors = [];

        $roomArrangements = collect($request->input('booking.roomArrangements'));

        $allGuests = collect($request->validated()['guests'])->filter(fn($guest) => empty($guest['deleted_at']));

        if ($roomArrangements->isNotEmpty()) {
            $allRoomDates = $roomArrangements->map(function ($room) {
                return [
                    'start' => Carbon::parse($room['dates']['start'])->format('Y-m-d'),
                    'end' => Carbon::parse($room['dates']['end'])->format('Y-m-d')
                ];
            });

            $earliestRoomStart = $allRoomDates->min('start');
            $latestRoomEnd = $allRoomDates->max('end');

            foreach ($allGuests as $guest) {
                $guestCheckIn = Carbon::parse($guest['dates']['start'])->format('Y-m-d');
                $guestCheckOut = Carbon::parse($guest['dates']['end'])->format('Y-m-d');

                if ($guestCheckIn < $earliestRoomStart || $guestCheckOut > $latestRoomEnd) {
                    $guestName = $guest['firstName'] . ' ' . $guest['lastName'];
                    $errors[] = "Guest {$guestName}'s travel dates ({$guestCheckIn} to {$guestCheckOut}) are outside the room arrangements range ({$earliestRoomStart} to {$latestRoomEnd}). Please adjust room dates or guest travel dates.";
                }
            }
        }

        foreach ($roomArrangements as $roomArrangement) {
            $roomBlock = RoomBlock::with('room', 'child_rates')->find($roomArrangement['room']);

            if (!$roomBlock) {
                continue;
            }

            $roomCheckIn = Carbon::parse($roomArrangement['dates']['start'])->format('Y-m-d');
            $roomCheckOut = Carbon::parse($roomArrangement['dates']['end'])->format('Y-m-d');

            $childRate = $roomBlock->child_rates->sortByDesc('to')->first();
            $childAge = $roomBlock->room->adults_only ? 17 : ($childRate ? $childRate->to : 17);
            $adultAge = $childAge + 1;

            $guests = $allGuests->filter(function ($guest) use ($roomCheckIn, $roomCheckOut) {
                $guestCheckIn = Carbon::parse($guest['dates']['start'])->format('Y-m-d');
                $guestCheckOut = Carbon::parse($guest['dates']['end'])->format('Y-m-d');

                return $guestCheckIn < $roomCheckOut && $guestCheckOut > $roomCheckIn;
            });

            if ($guests->isEmpty()) {
                continue;
            }

            $adults = $guests->filter(function ($guest) use ($group, $childAge) {
                return Carbon::parse($guest['birthDate'])->diffInYears($guest['dates']['start']) > $childAge;
            });

            $children = $guests->filter(function ($guest) use ($group, $childAge) {
                return Carbon::parse($guest['birthDate'])->diffInYears($guest['dates']['start']) <= $childAge;
            });

            if (!$roomBlock->room->adults_only && Carbon::parse($guests->first()['birthDate'])->diffInYears($guests->first()['dates']['start']) <= $childAge) {
                $errors[] = "Room {$roomBlock->room->name}: Guest 1 must be an adult. Guests at-least {$adultAge} years old are considered adults.";
            }

            if ($roomBlock->room->adults_only && $children->count() > 0) {
                $errors[] = "Room {$roomBlock->room->name} is for adults only. Guests at-least {$adultAge} years old are considered adults.";
            }

            if (!$roomBlock->room->adults_only && $adults->count() > $roomBlock->room->max_adults) {
                $errors[] = "Room {$roomBlock->room->name} has a maximum limit of {$roomBlock->room->max_adults} adults. You have added {$adults->count()} adults. Guests at-least {$adultAge} years old are considered adults.";
            }

            if (!$roomBlock->room->adults_only && $children->count() > $roomBlock->room->max_children) {
                $errors[] = "Room {$roomBlock->room->name} has a maximum limit of {$roomBlock->room->max_children} children. You have added {$children->count()} children. Guests upto {$childAge} years old are considered children.";
            }

            if ($guests->count() > $roomBlock->room->max_occupants) {
                $errors[] = 'Your request to add a guest cannot be submitted as it exceeds the maximum occupancy permitted for the room category. Please choose another room category or email <a class="has-text-link" href="mailto:'.config('emails.groups').'">'.config('emails.groups').'</a> with questions.';
            }
        }

        if (!$request->ignoreGuestError && count($errors) > 0) {
            return response()->json([
                'errors' => $errors
            ]);
        }

        $hasChanges = $this->somethingChanged($booking, $request);
        $hasBalanceDueDatePassed = now()->gt($booking->group->balance_due_date);
        $currentClientHasPaymentPlan = $currentClient->paymentArrangements->count() > 0;
        $paymentDetails = [];

        if ($hasChanges) {
            $guestsRequiringInsurancePayment = $request->input('guestsRequiringInsurancePayment', []);

            $currentClientGuestsRequiringInsurance = collect($guestsRequiringInsurancePayment)->filter(function($guestReq) use ($request, $currentClient) {
                $guestFromRequest = collect($request->input('guests'))->firstWhere('id', $guestReq['id'] ?? null)
                    ?? collect($request->input('guests'))->where('index', $guestReq['index'] ?? null)->first();

                return $guestFromRequest && $guestFromRequest['client'] == $currentClient->id;
            });

            $addGuestWithTI = $currentClientGuestsRequiringInsurance->isNotEmpty();
            $existingClientIds = $booking->clients->pluck('id')->toArray();
            $showNewClientSuccessMessage = collect($request->input('guests'))
                ->filter(fn($g) => empty($g['deleted_at']))
                ->contains(function($requestGuest) use ($booking, $existingClientIds) {
                    $isNewClient = !is_numeric($requestGuest['client']) || !in_array($requestGuest['client'], $existingClientIds);

                    if (!$isNewClient) {
                        return false;
                    }

                    $currentGuest = $booking->guests->firstWhere('id', $requestGuest['id'] ?? null);

                    if (!$currentGuest) {
                        return true;
                    }

                    return $currentGuest->booking_client_id != $requestGuest['client'];
                });

            if(!$group->is_fit){
                $validationResult = $this->checkReservationChangeViolations($booking, $request);
                $messages = $validationResult['messages'];
                $adminMessages = $validationResult['adminMessages'];
                $shouldCCGroupsEmail = $validationResult['shouldCCGroupsEmail'];

                if (!empty($messages) && !$request->input('minimumNightsExceptionAccepted')) {
                    return response()->json([
                        'requiresConfirmation' => true,
                        'messages' => $messages,
                    ], 200);
                }

                $paymentDetails = [];
                if (!empty($adminMessages)) {
                    $paymentDetails['confirmationMessages'] = $adminMessages;
                    $paymentDetails['shouldCCGroupsEmail'] = $shouldCCGroupsEmail;
                }
             }

            if ($request->input('confirm')) {
                if(!$group->is_fit){
                    $bookingPreview = $this->getBookingPreview($request, $booking);
                    $currentClientAttachWithAnyGuests = collect($request->input('guests'))->where('client', $currentClient->id)->isNotEmpty();
                    $costCalculation = $this->calculateChangeCosts($bookingPreview, $group, $booking, $guestsRequiringInsurancePayment, $currentClient->id, $currentClientAttachWithAnyGuests);

                    $courtesyCredits = [];
                    $clientAmountToPay = $costCalculation['amountToPay'];

                    $otherCourtesyCredits = $this->sendPaymentNotificationsToOtherClients($booking, $costCalculation['otherClientsPayments'] ?? []);
                    $courtesyCredits = array_merge($courtesyCredits, $otherCourtesyCredits);

                    if ($clientAmountToPay < 0) {
                        $existingCredit = collect($courtesyCredits)->firstWhere('clientId', $currentClient->id);
                        if (!$existingCredit) {
                            $courtesyCredits[] = [
                                'clientId' => $currentClient->id,
                                'amount' => $clientAmountToPay,
                            ];
                        }

                        $clientBooking = $booking->clients()->where('id', $currentClient->id)->first();
                        if ($clientBooking && $clientBooking->client) {
                            try {
                                $clientBooking->client->notify(new ClientPaymentRequired(
                                    $clientBooking,
                                    [
                                        'amountToPay' => $clientAmountToPay,
                                    ],
                                    'guest change adjustment'
                                ));
                            } catch (\Exception $e) {
                                // Silent fail
                            }
                        }
                    }

                    if ($request->has('payment') && (($hasBalanceDueDatePassed && !$currentClientHasPaymentPlan) || $addGuestWithTI)) {
                        $paymentData = $request->input('payment');
                        $this->validateReservationPayment($paymentData);
                        $penalty = $this->calculateRemovedGuestPenalties($booking, $request);
                        $paymentDetails = array_merge($paymentDetails, $this->applyPaymentChangesToBooking($booking, $paymentData, $penalty, $courtesyCredits));
                    }else {
                        $penalty = $this->calculateRemovedGuestPenalties($booking, $request);
                        $paymentData['email'] = $request->input('booking.email');
                        $paymentDetails = array_merge($paymentDetails, $this->applyPaymentChangesToBooking($booking, $paymentData, $penalty, $courtesyCredits));
                    }
                }

                $proposedBooking = $this->createProposedBookingSnapshot($booking, $request);
                $proposedSnapshot = GuestChange::snapshot($proposedBooking);

                StageGuestReservation::dispatch($currentClient->id, $booking->id, $proposedSnapshot, $paymentDetails)->delay(now()->addSeconds(15));

                return response()->json([
                    'message' => 'Reservation changes submitted for approval',
                        'hasPaymentPlan' => $currentClientHasPaymentPlan,
                        'showFitGroupSuccessMessage' => $group->is_fit,
                        'showSuccess' => true,
                        'showNewClientSuccessMessage' => $showNewClientSuccessMessage,
                    ], 200);
            } else {
                $bookingPreview = $this->getBookingPreview($request, $booking);
                $currentClientAttachWithAnyGuests = collect($request->input('guests'))->where('client', $currentClient->id)->isNotEmpty();
                $costCalculation = $this->calculateChangeCosts($bookingPreview, $group, $booking, $guestsRequiringInsurancePayment, $currentClient->id, $currentClientAttachWithAnyGuests);

                if(($hasBalanceDueDatePassed && !$currentClientHasPaymentPlan && !$group->is_fit) || $addGuestWithTI) {

                    $clientBooking = $booking->clients()
                        ->where('id', $currentClient->id)
                        ->with(['client', 'card'])
                        ->first();

                $clientAmountToPay = $costCalculation['amountToPay'];
                $currentClientHasPA = $costCalculation['currentClientHasPaymentPlan'];

                if($clientAmountToPay > 0 && $currentClientAttachWithAnyGuests && (!$currentClientHasPA || $addGuestWithTI)) {
                    $showOnlyInsurance = (!$hasBalanceDueDatePassed || ($hasBalanceDueDatePassed && $currentClientHasPaymentPlan)) && $addGuestWithTI;

                        return response()->json([
                            'hasPaymentPlan' => $currentClientHasPaymentPlan,
                            'paymentDetails' => [
                                'total' => $costCalculation['total'],
                                'payments' => $costCalculation['payments'],
                                'balanceAmount' => $costCalculation['balanceAmount'],
                                'totalRequired' => number_format($clientAmountToPay, 2),
                                'totalAfterChange' => $costCalculation['totalAfterChange'],
                                'changesCost' => $costCalculation['changesCost'],
                                'changeFee' => $costCalculation['changeFee'],
                                'insuranceCost' => $costCalculation['insuranceCost'],
                                'showOnlyInsurance' => $showOnlyInsurance,
                            ],
                            'card' => $clientBooking ? [
                                'name' => $clientBooking->card->name ?? null,
                                'type' => $clientBooking->card->type ?? null,
                                'lastDigits' => $clientBooking->card->last_digits ?? null
                            ] : null,
                            'showSuccess' => false,
                            'client' => $clientBooking ? [
                                'name' => $clientBooking->name,
                                'email' => $clientBooking->client->email
                            ] : null,
                        ], 200);
                    } else {
                        $paymentData['email'] = $clientBooking->client->email;
                        $paymentData['changeFee'] = $costCalculation['changeFee'];

                        $courtesyCredits = [];
                        $clientAmountToPay = $costCalculation['amountToPay'];
                        if ($clientAmountToPay < 0) {
                            $courtesyCredits[] = [
                                'clientId' => $currentClient->id,
                                'amount' => $clientAmountToPay,
                            ];

                            if ($clientBooking->client) {
                                $clientBooking->client->notify(new ClientPaymentRequired(
                                    $clientBooking,
                                    [
                                        'amountToPay' => $clientAmountToPay,
                                    ],
                                    'guest change adjustment'
                                ));
                            }
                        }

                        $otherCourtesyCredits = $this->sendPaymentNotificationsToOtherClients($booking, $costCalculation['otherClientsPayments'] ?? []);
                        $courtesyCredits = array_merge($courtesyCredits, $otherCourtesyCredits);

                        $penalty = $this->calculateRemovedGuestPenalties($booking, $request);
                        $paymentDetails = array_merge($paymentDetails, $this->applyPaymentChangesToBooking($booking, $paymentData, $penalty, $courtesyCredits));

                        $proposedBooking = $this->createProposedBookingSnapshot($booking, $request);
                        $proposedSnapshot = GuestChange::snapshot($proposedBooking);

                        StageGuestReservation::dispatch($currentClient->id, $booking->id, $proposedSnapshot, $paymentDetails)->delay(now()->addSeconds(15));
                        return response()->json([
                            'hasPaymentPlan' => $currentClientHasPaymentPlan,
                            'showFitGroupSuccessMessage' => $group->is_fit,
                            'showSuccess' => true,
                            'showNewClientSuccessMessage' => $showNewClientSuccessMessage,
                            'message' => 'Reservation changes submitted for approval',
                        ], 200);
                    }
                } else if($hasBalanceDueDatePassed && !$currentClientHasPaymentPlan && $group->is_fit) {
                        $proposedBooking = $this->createProposedBookingSnapshot($booking, $request);
                        $proposedSnapshot = GuestChange::snapshot($proposedBooking);
                        StageGuestReservation::dispatch($currentClient->id, $booking->id, $proposedSnapshot, $paymentDetails)->delay(now()->addSeconds(15));

                    return response()->json([
                        'hasPaymentPlan' => $currentClientHasPaymentPlan,
                        'showFitGroupSuccessMessage' => $group->is_fit,
                        'showSuccess' => true,
                        'showNewClientSuccessMessage' => $showNewClientSuccessMessage,
                        'message' => 'Reservation changes submitted for approval',
                    ], 200);
                }

                return response()->json([ 'hasPaymentPlan' => $currentClientHasPaymentPlan, 'showSuccess' => true], 200);
            }
        } else {
            return response()->json([
                'message' => 'No changes detected. Reservation remains unchanged.',
                'noChangesDetected' => true,
                'showFitGroupSuccessMessage' => $group->is_fit,
                'showSuccess' => true,
            ], 200);
        }
    }

    private function createProposedBookingSnapshot(Booking $booking, $request)
    {
        $proposedBooking = new Booking();
        $proposedBooking->id = $booking->id;
        $proposedBooking->group_id = $booking->group_id;
        $proposedBooking->special_requests = $request->input('booking.specialRequests');
        $proposedBooking->is_paid = $booking->is_paid;
        $proposedBooking->confirmed_at = $booking->confirmed_at;
        $proposedBooking->notes = $booking->notes;
        $proposedBooking->order = $booking->order;
        $proposedBooking->deposit = $booking->deposit;
        $proposedBooking->deposit_type = $booking->deposit_type;
        $proposedBooking->custom_group_airport = $booking->custom_group_airport;
        $proposedBooking->created_at = $booking->created_at;
        $proposedBooking->updated_at = $booking->updated_at;
        $proposedBooking->deleted_at = $booking->deleted_at;

        $proposedRoomBlocks = collect();
        $existingRoomBlocks = $booking->roomBlocks;

        foreach ($request->input('booking.roomArrangements') as $roomArrangement) {
            $existingRoomBlock = $existingRoomBlocks->firstWhere('room_block_id', $roomArrangement['room']);

            if ($existingRoomBlock) {
                $proposedRoomBlock = clone $existingRoomBlock;

                $proposedRoomBlock->fill([
                    'booking_id' => $booking->id,
                    'room_block_id' => $roomArrangement['room'],
                    'bed' => $roomArrangement['bed'],
                    'check_in' => $roomArrangement['dates']['start'],
                    'check_out' => $roomArrangement['dates']['end'],
                ]);

                $proposedRoomBlock->pivot = [
                    'booking_id' => $booking->id,
                    'room_block_id' => $roomArrangement['room'],
                    'bed' => $roomArrangement['bed'],
                    'check_in' => $roomArrangement['dates']['start'],
                    'check_out' => $roomArrangement['dates']['end'],
                ];

                $proposedRoomBlocks->push($proposedRoomBlock);
            } else {
                $roomBlock = RoomBlock::with('room.hotel')->find($roomArrangement['room']);

                if ($roomBlock) {
                    $proposedRoomBlock = new BookingRoomBlock([
                        'id' => $roomArrangement['room'],
                        'booking_id' => $booking->id,
                        'room_block_id' => $roomArrangement['room'],
                        'bed' => $roomArrangement['bed'],
                        'check_in' => $roomArrangement['dates']['start'],
                        'check_out' => $roomArrangement['dates']['end'],
                    ]);

                    $proposedRoomBlock->pivot = [
                        'booking_id' => $booking->id,
                        'room_block_id' => $roomArrangement['room'],
                        'bed' => $roomArrangement['bed'],
                        'check_in' => $roomArrangement['dates']['start'],
                        'check_out' => $roomArrangement['dates']['end'],
                    ];

                    $proposedRoomBlock->setRelation('room', $roomBlock->room);
                    $proposedRoomBlocks->push($proposedRoomBlock);
                }
            }
        }

        $proposedGuests = collect();
        $allExistingGuests = Guest::withTrashed()
        ->whereHas('booking_client', function ($query) use ($booking) {
            $query->where('booking_id', $booking->id);
        })
        ->get();

        foreach ($request->input('guests') as $newGuest) {
            $existingGuest = $allExistingGuests->firstWhere('id', $newGuest['id'] ?? 0);

            if ($existingGuest) {
                $proposedGuest = clone $existingGuest;

                $proposedGuest->fill([
                    'booking_client_id' => $newGuest['client'],
                    'first_name' => $newGuest['firstName'],
                    'last_name' => $newGuest['lastName'],
                    'gender' => $newGuest['gender'],
                    'birth_date' => $newGuest['birthDate'],
                    'check_in' => $newGuest['dates']['start'],
                    'check_out' => $newGuest['dates']['end'],
                    'deleted_at' => $newGuest['deleted_at'] ? now() : null,
                    'insurance' => isset($newGuest['insurance']) ? (is_null($newGuest['insurance']) ? null : ($newGuest['insurance'] ? 1 : 0)) : null,
                    'transportation' => is_null($newGuest['transportation']) ? null : ($newGuest['transportation'] ? 1 : 0),
                    'transportation_type' => is_null($newGuest['transportation']) ? null : ($newGuest['transportation'] ? ($newGuest['transportation_type'] ?? 1) : null),
                    'custom_group_airport' => is_null($newGuest['transportation']) ? null : ($newGuest['transportation'] ? ($newGuest['customGroupAirport'] ?? $booking->group->defaultAirport()->id) : null),
                ]);

                $proposedGuests->push($proposedGuest);
            } else {
                $proposedGuest = new Guest([
                    'id' => $newGuest['id'] ?? null,
                    'booking_client_id' => $newGuest['client'],
                    'first_name' => $newGuest['firstName'],
                    'last_name' => $newGuest['lastName'],
                    'gender' => $newGuest['gender'],
                    'birth_date' => $newGuest['birthDate'],
                    'check_in' => $newGuest['dates']['start'],
                    'check_out' => $newGuest['dates']['end'],
                    'deleted_at' => null,
                    'insurance' => isset($newGuest['insurance']) ? (is_null($newGuest['insurance']) ? null : ($newGuest['insurance'] ? 1 : 0)) : null,
                    'transportation' => is_null($newGuest['transportation']) ? null : ($newGuest['transportation'] ? 1 : 0),
                    'transportation_type' => is_null($newGuest['transportation']) ? null : ($newGuest['transportation'] ? ($newGuest['transportation_type'] ?? 1) : null),
                    'custom_group_airport' => is_null($newGuest['transportation']) ? null : ($newGuest['transportation'] ? ($newGuest['customGroupAirport'] ?? $booking->group->defaultAirport()->id) : null),
                ]);

                $proposedGuests->push($proposedGuest);
            }
        }

        $requestGuestIds = collect($request->input('guests'))->pluck('id')->filter()->toArray();
        $softDeletedGuests = $allExistingGuests->where('deleted_at', '!=', null)->whereNotIn('id', $requestGuestIds);

        foreach ($softDeletedGuests as $softDeletedGuest) {
            $proposedGuest = clone $softDeletedGuest;
            $proposedGuests->push($proposedGuest);
        }

        $proposedClients = collect();
        $existingClients = $booking->clients;
        $newClientEmailMap = [];

        if ($request->input('hasSeperateClients') && $request->input('seperateClients')) {
            $existingClientEmails = $existingClients->pluck('client.email')->filter()->toArray();
            $newSeperateClients = array_filter($request->input('seperateClients', []), function ($client) use ($existingClientEmails) {
                return !in_array($client['email'], $existingClientEmails);
            });

            foreach ($newSeperateClients as $index => $newClient) {
                $tempClientId = 'new_client_' . $newClient['email'];
                $newClientEmailMap[$newClient['email']] = $tempClientId;

                $proposedNewClient = new BookingClient([
                    'id' => $tempClientId,
                    'first_name' => $newClient['firstName'],
                    'last_name' => $newClient['lastName'],
                    'telephone' => $newClient['phone'],
                    'email' => $newClient['email'],
                ]);

                $clientGuests = $proposedGuests->filter(function ($guest) use ($newClient, $newClientEmailMap) {
                    return $guest->booking_client_id === $newClient['email'] ||
                           $guest->booking_client_id === $newClientEmailMap[$newClient['email']];
                });

                $proposedNewClient->setRelation('guests', $clientGuests);
                $proposedClients->push($proposedNewClient);
            }
        }

        foreach ($existingClients as $existingClient) {
            $proposedClient = clone $existingClient;

            $clientGuests = $proposedGuests->filter(function ($guest) use ($existingClient) {
                return $guest->booking_client_id == $existingClient->id;
            });

            $proposedClient->setRelation('guests', $clientGuests);
            $proposedClients->push($proposedClient);
        }

        $proposedBooking->setRelation('roomBlocks', $proposedRoomBlocks);
        $proposedBooking->setRelation('clients', $proposedClients);
        $proposedBooking->newSeperateClients = $request->input('seperateClients', []);

        return $proposedBooking;
    }

    private function somethingChanged(Booking $booking, $request)
    {
        if ($booking->special_requests !== $request->input('booking.specialRequests')) {
            return true;
        }

        $currentRoomBlocks = $booking->roomBlocks;
        $requestRoomArrangements = $request->input('booking.roomArrangements');

        if (count($currentRoomBlocks) !== count($requestRoomArrangements)) {
            return true;
        }

        foreach ($requestRoomArrangements as $index => $roomArrangement) {
            $currentRoomBlock = $currentRoomBlocks->get($index);

            if (!$currentRoomBlock) {
                return true;
            }

            $currentCheckIn = $currentRoomBlock->pivot->check_in ? date('Y-m-d', strtotime($currentRoomBlock->pivot->check_in)) : null;
            $currentCheckOut = $currentRoomBlock->pivot->check_out ? date('Y-m-d', strtotime($currentRoomBlock->pivot->check_out)) : null;
            $requestCheckIn = $roomArrangement['dates']['start'] ? date('Y-m-d', strtotime($roomArrangement['dates']['start'])) : null;
            $requestCheckOut = $roomArrangement['dates']['end'] ? date('Y-m-d', strtotime($roomArrangement['dates']['end'])) : null;

            if ($currentRoomBlock->pivot->room_block_id != $roomArrangement['room'] ||
                $currentRoomBlock->pivot->bed != $roomArrangement['bed'] ||
                $currentCheckIn != $requestCheckIn ||
                $currentCheckOut != $requestCheckOut) {
                return true;
            }
        }

        $currentGuests = $booking->guests;
        $requestGuests = $request->input('guests');

        if (count($currentGuests) !== count($requestGuests)) {
            return true;
        }

        foreach ($requestGuests as $index => $requestGuest) {
            $currentGuest = $currentGuests->get($index);

            if (!$currentGuest) {
                return true;
            }

            $currentCheckIn = $currentGuest->check_in ? date('Y-m-d', strtotime($currentGuest->check_in)) : null;
            $currentCheckOut = $currentGuest->check_out ? date('Y-m-d', strtotime($currentGuest->check_out)) : null;
            $requestCheckIn = $requestGuest['dates']['start'] ? date('Y-m-d', strtotime($requestGuest['dates']['start'])) : null;
            $requestCheckOut = $requestGuest['dates']['end'] ? date('Y-m-d', strtotime($requestGuest['dates']['end'])) : null;
            $currentBirthDate = $currentGuest->birth_date ? date('Y-m-d', strtotime($currentGuest->birth_date)) : null;
            $requestBirthDate = $requestGuest['birthDate'] ? date('Y-m-d', strtotime($requestGuest['birthDate'])) : null;
            $currentDeletedAt = $currentGuest->deleted_at ? 'true' : 'false';
            $requestDeletedAt = $requestGuest['deleted_at'] ? 'true' : 'false';

            if (!isset($requestGuest['insurance'])) {
                if ($currentGuest->insurance !== null) {
                    return true;
                }
            } else {
                $requestInsuranceValue = $requestGuest['insurance'] ? 1 : 0;
                $currentInsuranceValue = $currentGuest->insurance === null ? null : ($currentGuest->insurance ? 1 : 0);

                if ($currentInsuranceValue !== $requestInsuranceValue) {
                    return true;
                }
            }

            $requestTransportation = is_null($requestGuest['transportation']) ? null : ($requestGuest['transportation'] ? 1 : 0);
            $currentTransportation = $currentGuest->transportation === null ? null : ($currentGuest->transportation ? 1 : 0);
            $requestTransportationType = is_null($requestGuest['transportation']) ? null : ($requestGuest['transportation'] ? ($requestGuest['transportation_type'] ?? 1) : null);
            $currentTransportationType = $currentGuest->transportation === null ? null : ($currentGuest->transportation ? $currentGuest->transportation_type : null);
            $requestCustomAirport = is_null($requestGuest['transportation']) ? null : ($requestGuest['transportation'] ? ($requestGuest['customGroupAirport'] ?? $booking->group->defaultAirport()->id) : null);
            $currentCustomAirport = $currentGuest->transportation === null ? null : ($currentGuest->transportation ? $currentGuest->custom_group_airport : null);

            if ($currentGuest->first_name != $requestGuest['firstName'] ||
                $currentGuest->last_name != $requestGuest['lastName'] ||
                $currentGuest->gender != $requestGuest['gender'] ||
                $currentBirthDate != $requestBirthDate ||
                $currentCheckIn != $requestCheckIn ||
                $currentCheckOut != $requestCheckOut ||
                $currentTransportation != $requestTransportation ||
                $currentTransportationType != $requestTransportationType ||
                $currentCustomAirport != $requestCustomAirport ||
                $currentGuest->booking_client_id != $requestGuest['client'] ||
                $currentDeletedAt != $requestDeletedAt) {
                    return true;
                }
        }

        return false;
    }

    private function validateReservationPayment($paymentData)
    {
        $errors = [];

        $rules = [
            'amount' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) use ($paymentData) {
                    if ($value < $paymentData['totalRequired']) {
                        $fail('You must pay at least $' . $paymentData['totalRequired']. ' for these changes.');
                    }
                }
            ],
            'useCardOnFile' => ['required', 'boolean'],
            'updateCardOnFile' => ['sometimes', 'boolean'],
            'confirmation.accept' => ['required'],
            'confirmation.signature' => ['required'],
            'card.name' => ['required_if:useCardOnFile,false', 'string', 'max:100'],
            'card.number' => ['required_if:useCardOnFile,false', 'digits_between:15,16'],
            'card.expMonth' => ['required_if:useCardOnFile,false', 'digits:2'],
            'card.expYear' => ['required_if:useCardOnFile,false', 'numeric', 'min:' . now()->year],
            'card.code' => ['required_if:useCardOnFile,false'],
            'address.city' => ['required_if:useCardOnFile,false', 'string', 'max:50'],
            'address.line1' => ['required_if:useCardOnFile,false', 'string', 'max:200'],
            'address.line2' => ['nullable', 'string', 'max:200'],
            'address.zipCode' => ['required_if:useCardOnFile,false', 'string', 'min:3', 'max:20'],
        ];

        if (($paymentData['address']['country'] ?? null) === '0') {
            $rules['address.otherCountry'] = ['required_if:useCardOnFile,false', 'string', 'max:50'];
            $rules['address.otherState'] = ['required_if:useCardOnFile,false', 'string', 'max:50'];
        } else {
            $rules['address.country'] = ['required_if:useCardOnFile,false', 'integer', 'exists:countries,id'];
            $rules['address.state'] = ['required_if:useCardOnFile,false', 'integer', 'exists:states,id'];
        }

        $validator = Validator::make($paymentData, $rules);
        $errors = $validator->errors()->toArray();

        $client = Client::where('email', $paymentData['email'])->first();
        if (!$client) {
            $errors['booking.email'][] = 'This email does not exist in our records.';
        }

        $bookingClient = $client ? $client->bookings()->where('reservation_code', $paymentData['code'])->first() : null;
        if (!$bookingClient) {
            $errors['code'][] = 'The booking reservation code is not valid.';
        }

        if ($paymentData['useCardOnFile'] && (!$bookingClient || !$bookingClient->card()->exists())) {
            $errors['useCardOnFile'][] = 'There is no card on file registered for this booking, you must enter your payment information below.';
        }

        $cardHolderName = $paymentData['useCardOnFile']
            ? ($bookingClient && $bookingClient->card ? $bookingClient->card->name : null)
            : ($paymentData['card']['name'] ?? null);
        if (isset($paymentData['confirmation']['signature']) && $cardHolderName && strtolower($paymentData['confirmation']['signature']) !== strtolower($cardHolderName)) {
            $errors['confirmation.signature'][] = 'You must type the full name associated with the selected card.';
        }

        if (isset($paymentData['insurance']['signature']) && $bookingClient && strtolower($paymentData['insurance']['signature']) !== strtolower($bookingClient->name)) {
            $errors['insurance.signature'][] = 'You must type your full name.';
        }

        if (!$paymentData['useCardOnFile']) {
            $creditCard = CreditCard::validCreditCard($paymentData['card']['number'] ?? null);
            if (($paymentData['card']['type'] ?? null) != $creditCard['type']) {
                $errors['card.type'][] = 'The credit card type does not match the number.';
            }

            if (!$creditCard['valid']) {
                $errors['card.number'][] = 'The card number is not valid.';
            } else if (!in_array($creditCard['type'], ['visa', 'mastercard', 'amex', 'discover'])) {
                $errors['card.number'][] = 'The card must be a Visa, Mastercard, American Express or Discover card.';
            }

            if (!CreditCard::validDate($paymentData['card']['expYear'] ?? null, $paymentData['card']['expMonth'] ?? null)) {
                $errors['card.expMonth'][] = 'The expiration date is not valid.';
                $errors['card.expYear'][] = 'The expiration date is not valid.';
            }

            $cvv = $paymentData['card']['code'] ?? null;
            if (!CreditCard::validCvc($cvv, $creditCard['type'])) {
                $errors['card.code'][] = 'The code in not valid for this card type.';
            }

            $cvvLength = strlen($cvv);
            if ($creditCard['type'] === 'amex' && $cvvLength !== 4) {
                $errors['card.code'][] = 'American Express requires a 4-digit CID.';
            } elseif (in_array($creditCard['type'], ['visa', 'mastercard', 'discover']) && $cvvLength !== 3) {
                $errors['card.code'][] = ucfirst($creditCard['type']) . ' requires a 3-digit CVV.';
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    protected function getBookingPreview(Request $request, Booking $booking)
    {
        $previewBooking = Booking::make([
            'group_id' => $booking->group_id,
            'special_requests' => $request->input('booking.specialRequests'),
        ]);

        $roomBlocks = collect();
        foreach ($request->input('booking.roomArrangements') as $roomArrangement) {
            $room_block = RoomBlock::where('id', $roomArrangement['room'])->first();

            if ($room_block) {
                $room_block->pivot = new Pivot([
                    'bed' => $roomArrangement['bed'],
                    'check_in' => $roomArrangement['dates']['start'],
                    'check_out' => $roomArrangement['dates']['end'],
                ], $previewBooking);
                $roomBlocks->push($room_block);
            }
        }

        $previewBooking->setRelation('roomBlocks', $roomBlocks);
        $bookingClients = [];
        $bookingGuests = [];
        $guestCount = 0;
        $guestsByClient = collect($request->input('guests'))->groupBy('client');

        $allClientsToProcess = collect();

        foreach ($booking->clients as $existingClient) {
            $allClientsToProcess->push([
                'type' => 'existing',
                'id' => $existingClient->id,
                'client_id' => $existingClient->client_id,
                'first_name' => $existingClient->first_name,
                'last_name' => $existingClient->last_name,
                'insurance' => $existingClient->insurance,
                'insurance_signed_at' => $existingClient->insurance_signed_at,
                'email' => $existingClient->client->email ?? null,
            ]);
        }

        if ($request->input('hasSeperateClients') && $request->input('seperateClients')) {
            $existingClientEmails = $booking->clients->pluck('client.email')->filter()->toArray();
            $newClients = array_filter($request->input('seperateClients', []), function ($client) use ($existingClientEmails) {
                return !in_array($client['email'], $existingClientEmails);
            });

            foreach ($newClients as $index => $newClient) {
                $allClientsToProcess->push([
                    'type' => 'new',
                    'id' => 'new_' . $index,
                    'client_id' => null,
                    'first_name' => $newClient['firstName'],
                    'last_name' => $newClient['lastName'],
                    'insurance' => null,
                    'insurance_signed_at' => null,
                    'email' => $newClient['email'],
                ]);
            }
        }

        foreach ($allClientsToProcess as $clientIndex => $clientData) {
            $bookingClient = BookingClient::make([
                'client_id' => $clientData['client_id'],
                'first_name' => $clientData['first_name'],
                'last_name' => $clientData['last_name'],
                'booking_id' => $booking->id,
                'insurance' => $clientData['insurance'],
                'insurance_signed_at' => $clientData['insurance_signed_at'],
            ]);

            $bookingClient->id = $clientData['type'] === 'existing' ? $clientData['id'] : (1000 + $clientIndex);

            $bookingClientGuests = [];
            $clientKey = $clientData['type'] === 'existing' ? $clientData['id'] : $clientData['email'];
            $clientGuests = $guestsByClient->get($clientKey, collect());
            foreach ($clientGuests as $guest) {
                if ($guest['deleted_at']) {
                    continue;
                }

                $guestCount++;
                $birthDate = null;

                if (isset($guest['id'])) {
                    $originalGuest = $booking->guests->firstWhere('id', $guest['id']);
                    if ($originalGuest) {
                        $birthDate = $originalGuest->birth_date;
                    }
                }
                if (!$birthDate && isset($guest['birthDate'])) {
                    $birthDate = $guest['birthDate'];
                }

                $bookingClientGuest = Guest::make([
                    'first_name' => $guest['firstName'],
                    'last_name' => $guest['lastName'],
                    'gender' => $guest['gender'] ?? '',
                    'check_in' => $guest['dates']['start'],
                    'check_out' => $guest['dates']['end'],
                    'insurance' => $guest['insurance'] ?? null,
                    'transportation' => $guest['transportation'] ?? false,
                    'transportation_type' => $guest['transportation'] ? ($guest['transportation_type'] ?? 1) : null,
                    'custom_group_airport' => $guest['transportation'] ? ($guest['customGroupAirport'] ?? $booking->group->defaultAirport()->id) : null,
                    'birth_date' => $birthDate
                ]);
                $bookingClientGuest->id = isset($guest['id']) ? $guest['id'] : $guestCount;
                $bookingClientGuest->booking_client_id = $clientData['type'] === 'existing' ? $clientData['id'] : $clientIndex;

                array_push($bookingClientGuests, $bookingClientGuest);
                array_push($bookingGuests, $bookingClientGuest);
            }

            $bookingClient->setRelation('guests', new Collection($bookingClientGuests));

            if ($booking->group->is_fit) {
                $bookingClient->setRelation('fitRate', $existingClient->fitRate);
            }

            $bookingClient->setRelation('extras', $existingClient->extras);
            array_push($bookingClients, $bookingClient);
        }

        $previewBooking->setRelation('clients', new Collection($bookingClients));
        $previewBooking->setRelation('guests', new Collection($bookingGuests));
        $previewBooking->setRelation('group', $booking->group);

        return $previewBooking;
    }

    protected function calculateChangeCosts($bookingPreview, $group, $originalBooking, $guestsRequiringInsurancePayment = [], $currentClient, $currentClientAttachWithAnyGuests) {
        $changeFee = $group->change_fee_amount ?? 0;
        $hasBalanceDueDatePassed = now()->gt($originalBooking->group->balance_due_date);

        $currentClientPayment = null;
        $otherClientsPayments = [];

        collect($bookingPreview->clients)->each(function($previewClient) use ($originalBooking, $bookingPreview, $guestsRequiringInsurancePayment, $currentClient, $hasBalanceDueDatePassed, &$currentClientPayment, &$otherClientsPayments, $changeFee) {
            $originalClient = $originalBooking->clients->firstWhere('id', $previewClient->id);

            if (!$originalClient) {
                return;
            }

            $originalClientGuestIds = $originalClient->guests()->whereNull('deleted_at')->pluck('id')->toArray();

            $previewClientGuestIds = $previewClient->guests->filter(fn($g) => !$g->deleted_at)->pluck('id')->filter()->toArray();

            $clientHasChanges = !empty(array_diff($originalClientGuestIds, $previewClientGuestIds)) ||
                               !empty(array_diff($previewClientGuestIds, $originalClientGuestIds)) ||
                               $previewClient->id == $currentClient;

            if (!$clientHasChanges && $previewClient->id != $currentClient) {
                return;
            }

            $clientHasPaymentPlan = $originalClient->paymentArrangements->count() > 0;

            $clientSubtotal = $originalBooking->getClientTotal($originalClient);
            $clientPayments = $originalClient->payments()
                ->whereNull('cancelled_at')
                ->where(fn($q) => $q->where('card_declined', false)->orWhereNull('card_declined'))
                ->sum('amount') ?? 0;

            $clientTotalAfterChanges = $bookingPreview->getClientTotal($previewClient);
            $clientBalance = $clientSubtotal - $clientPayments;
            $clientCostOfChanges = $clientTotalAfterChanges - $clientSubtotal;
            $clientChangeFee  =  $clientTotalAfterChanges  < $clientSubtotal ? 0 : $changeFee;
            $clientAmountToPay = ($clientTotalAfterChanges - $clientPayments) + $clientChangeFee;

            $insuranceCost = 0;
            if (!empty($guestsRequiringInsurancePayment) && $previewClient->id == $currentClient) {
                $clientBreakdown = $bookingPreview->breakdownClients(collect([$previewClient]))->first();

                collect($guestsRequiringInsurancePayment)->each(function($guestInfo) use ($clientBreakdown, &$insuranceCost) {
                    $index = $guestInfo['index'] ?? null;
                    $guestId = $guestInfo['id'] ?? null;
                    $guest = null;

                    if ($index !== null && isset($clientBreakdown->guests[$index])) {
                        $guest = $clientBreakdown->guests[$index];
                    }

                    if (!$guest && $guestId) {
                        $guest = collect($clientBreakdown->guests)->firstWhere('id', $guestId);
                    }

                    if ($guest && $guest->insuranceRate && $guest->insuranceRate->rate > 0) {
                        $insuranceCost += $guest->insuranceRate->rate;
                    }
                });

                if ($insuranceCost > 0 && (!$hasBalanceDueDatePassed || ($hasBalanceDueDatePassed && $clientHasPaymentPlan))) {
                    $tempAmountWithInsurance = ($clientTotalAfterChanges - $clientPayments) + $clientChangeFee;
                    if ($tempAmountWithInsurance > 0) {
                        $clientAmountToPay = $insuranceCost;
                    }
                }
            }

            $clientPaymentInfo = [
                'clientId' => $previewClient->id,
                'clientName' => $previewClient->first_name . ' ' . $previewClient->last_name,
                'clientEmail' => $originalClient->client->email ?? null,
                'subtotal' => $clientSubtotal,
                'payments' => $clientPayments,
                'balance' => $clientBalance,
                'totalAfterChanges' => $clientTotalAfterChanges,
                'costOfChanges' => $clientCostOfChanges,
                'amountToPay' => $clientAmountToPay,
                'insuranceCost' => $insuranceCost,
                'changeFee' => $clientChangeFee,
                'requiresPayment' => $clientAmountToPay > 0,
                'hasPaymentPlan' => $clientHasPaymentPlan,
            ];

            if ($previewClient->id == $currentClient) {
                $currentClientPayment = $clientPaymentInfo;
            } else if ($clientAmountToPay != 0 && $clientHasChanges && $previewClient->id != $currentClient) {
                if ($clientAmountToPay < 0) {
                    $otherClientsPayments[] = [
                        'clientId' => $previewClient->id,
                        'amountToPay' => $clientAmountToPay,
                    ];
                } else if (!$clientHasPaymentPlan) {
                    $otherClientsPayments[] = [
                        'clientId' => $previewClient->id,
                        'amountToPay' => $clientAmountToPay,
                    ];
                }
            }
        });

        if ($currentClientPayment) {
            $currentClientAmountToPay = $currentClientPayment['amountToPay'];
            $currentClientHasPA = $currentClientPayment['hasPaymentPlan'];

            return [
                'total' => number_format($currentClientPayment['subtotal'], 2),
                'payments' => number_format($currentClientPayment['payments'], 2),
                'balanceAmount' => number_format($currentClientPayment['balance'], 2),
                'totalAfterChange' => number_format($currentClientPayment['totalAfterChanges'], 2),
                'changesCost' => number_format($currentClientPayment['costOfChanges'], 2),
                'changeFee' => number_format($currentClientPayment['changeFee'], 2),
                'amountToPay' => $currentClientAmountToPay,
                'insuranceCost' => number_format($currentClientPayment['insuranceCost'], 2),
                'otherClientsPayments' => $otherClientsPayments,
                'currentClientHasPaymentPlan' => $currentClientHasPA,
            ];
        }

        return [
            'total' => '0.00',
            'payments' => '0.00',
            'balanceAmount' => '0.00',
            'totalAfterChange' => '0.00',
            'changesCost' => '0.00',
            'changeFee' => number_format($changeFee, 2),
            'amountToPay' => 0,
            'insuranceCost' => '0.00',
            'otherClientsPayments' => $otherClientsPayments,
            'currentClientHasPaymentPlan' => false,
        ];
    }

    protected function applyPaymentChangesToBooking($booking, $paymentData, $penalty = null, $courtesyCredits = [])
    {
        $clientBooking = $booking->clients()
            ->whereHas('client', function($query) use ($paymentData) {
                $query->where('email', $paymentData['email']);
            })
            ->first();

        $client = $clientBooking->client;

        $paymentIds = [];

        if(isset($paymentData['totalRequired']) && $paymentData['totalRequired'] > 0) {
            $card = $paymentData['useCardOnFile'] ? $clientBooking->card : $client->cards()->create([
                'name' => $paymentData['card']['name'],
                'type' => $paymentData['card']['type'],
                'number' => $paymentData['card']['number'],
                'expiration_date' => $paymentData['card']['expMonth'] . $paymentData['card']['expYear'],
                'code' => $paymentData['card']['code'],
                'address_id' => $client->addresses()->create([
                    'country_id' => $paymentData['address']['country'] ? $paymentData['address']['country'] : null,
                    'other_country' => $paymentData['address']['country'] ? null : $paymentData['address']['otherCountry'],
                    'state_id' => $paymentData['address']['country'] ? $paymentData['address']['state'] : null,
                    'other_state' => $paymentData['address']['country'] ? null : $paymentData['address']['otherState'],
                    'city' => $paymentData['address']['city'],
                    'line_1' => $paymentData['address']['line1'],
                    'line_2' => $paymentData['address']['line2'] ?? null,
                    'zip_code' => $paymentData['address']['zipCode']
                ])->id
            ]);

            if (!$clientBooking->card()->exists() || $paymentData['updateCardOnFile']) {
                $clientBooking->card()->associate($card)->save();
            }

            $paymentIds[] = $clientBooking->payments()->create([
                'amount' => $paymentData['amount'],
                'card_id' => $card->id
            ])->id;
        }

        if (!empty($courtesyCredits)) {
            foreach ($courtesyCredits as $courtesyCredit) {
                $targetClientId = $courtesyCredit['clientId'];
                $targetClient = $booking->clients()->where('id', $targetClientId)->first();

                if (!$targetClient) {
                    continue;
                }

                $paymentData = [
                    'amount' => $courtesyCredit['amount'],
                    'notes' => 'Courtesy Credit - Guest Change Adjustment',
                ];

                if ($targetClient->card_id) {
                    $paymentData['card_id'] = $targetClient->card_id ?? null;
                }

                try {
                    $paymentIds[] = $targetClient->payments()->create($paymentData)->id;
                } catch (\Exception $e) {
                    // Silent fail
                }
            }
        }

        $extraIds = [];

        if (isset($paymentData['changeFee']) && $paymentData['changeFee'] > 0) {
            $extraIds[] = $clientBooking->extras()->create([
                'description' => 'Change Fee',
                'price' => $paymentData['changeFee'],
                'quantity' => 1
            ])->id;
        }

        if (isset($penalty) && is_array($penalty)) {
            if (!empty($penalty['accommodationCharges'])) {
                foreach($penalty['accommodationCharges'] as $accommodationCharge) {
                    $targetClientId = $accommodationCharge['clientId'];
                    $targetClient = $booking->clients()->where('id', $targetClientId)->first();

                    if (!$targetClient) {
                        continue;
                    }

                    if ($accommodationCharge['amount'] > 0) {
                        $extra = $targetClient->extras()->create([
                            'description' => $accommodationCharge['description'],
                            'price' => $accommodationCharge['amount'],
                            'quantity' => 1
                        ]);
                        $extraIds[] = $extra->id;
                    }
                }
            }

            if (!empty($penalty['traveInsuranceDetails'])) {
                foreach($penalty['traveInsuranceDetails'] as $travelInsurance) {
                    $targetClientId = $travelInsurance['clientId'] ?? null;
                    $targetClient = $targetClientId ? $booking->clients()->where('id', $targetClientId)->first() : null;

                    if (!$targetClient) {
                        $targetClient = $clientBooking;
                    }

                    if ($travelInsurance['amount'] > 0) {
                        $extra = $targetClient->extras()->create([
                            'description' => $travelInsurance['description'],
                            'price' => $travelInsurance['amount'],
                            'quantity' => 1
                        ]);
                        $extraIds[] = $extra->id;
                    }
                }
            }

            if (!empty($penalty['travelInsuranceAdditions'])) {
                foreach($penalty['travelInsuranceAdditions'] as $insuranceAddition) {
                    $targetClientId = $insuranceAddition['clientId'];
                    $targetClient = $booking->clients()->where('id', $targetClientId)->first();

                    if (!$targetClient) {
                        continue;
                    }

                    if ($insuranceAddition['amount'] > 0) {
                        $extra = $targetClient->extras()->create([
                            'description' => $insuranceAddition['description'],
                            'price' => $insuranceAddition['amount'],
                            'quantity' => 1
                        ]);
                        $extraIds[] = $extra->id;
                    }
                }
            }

        }

        return [
            'payment_id' => $paymentIds ?? [],
            'extra_id' => $extraIds ?? []
        ];
    }


    private function calculateRemovedGuestPenalties($booking, $request)
    {
        $currentGuestIds = $booking->guests->pluck('id');
        $requestGuestIds = collect($request->input('guests'))->filter(fn ($guest) => empty($guest['deleted_at']))->pluck('id')->filter();
        $removedGuestIds = $currentGuestIds->diff($requestGuestIds);

        $accommodationCharges = [];
        $traveInsuranceDetails = [];
        $travelInsuranceAdditions = [];

        $bookingPreview = $this->getBookingPreview($request, $booking);
        $hasBalanceDueDatePassed = now()->gt($booking->group->balance_due_date);

        if($hasBalanceDueDatePassed){
            $booking->clients->each(function($originalClient) use ($booking, $bookingPreview, &$accommodationCharges) {
                $originalClientBreakdown = $booking->breakdownClients(collect([$originalClient]))->first();
                $originalClientAccommodation = $originalClientBreakdown->guests->sum(function($guest) {
                    return $guest->total - $guest->transportationTotal;
                });

                $previewClient = $bookingPreview->clients->firstWhere('id', $originalClient->id);

                if (!$previewClient) {
                    return;
                }

                $previewClientBreakdown = $bookingPreview->breakdownClients(collect([$previewClient]))->first();
                $newClientAccommodation = $previewClientBreakdown->guests->sum(function($guest) {
                    return $guest->total - $guest->transportationTotal;
                });

                $clientAccommodationReduction = $originalClientAccommodation - $newClientAccommodation;

                if ($clientAccommodationReduction > 0) {
                    $accommodationCharges[] = [
                        'amount' => $clientAccommodationReduction,
                        'description' => 'Non-Refundable Accommodations',
                        'clientId' => $originalClient->id,
                        'clientName' => $originalClient->name
                    ];
                }
            });
        }

        $booking->clients->each(function($originalClient) use ($booking, $bookingPreview, &$traveInsuranceDetails) {
            $originalClientBreakdown = $booking->breakdownClients(collect([$originalClient]))->first();
            $previewClient = $bookingPreview->clients->firstWhere('id', $originalClient->id);

            if (!$previewClient) {
                return;
            }

            $previewClientBreakdown = $bookingPreview->breakdownClients(collect([$previewClient]))->first();

            $originalClientBreakdown->guests->each(function($originalGuest) use ($previewClientBreakdown, $originalClient, &$traveInsuranceDetails) {
                $newGuest = $previewClientBreakdown->guests->firstWhere('id', $originalGuest->id);

                if ($newGuest && $originalGuest->insuranceRate && $newGuest->insuranceRate) {
                    $originalInsuranceRate = $originalGuest->insuranceRate->rate;
                    $newInsuranceRate = $newGuest->insuranceRate->rate;

                    $insuranceDifference = $originalInsuranceRate - $newInsuranceRate;

                    if ($insuranceDifference > 0) {
                        $traveInsuranceDetails[] = [
                            'amount' => $insuranceDifference,
                            'description' => "NON REFUNDABLE TRAVEL INSURANCE",
                            'clientId' => $originalClient->id,
                            'clientName' => $originalClient->name
                        ];
                    }
                }
            });
        });

        $removedGuestIds->each(function ($removedGuestId) use ($booking, &$traveInsuranceDetails) {
            $guest = $booking->guests->firstWhere('id', $removedGuestId);

            if (!$guest) {
                return;
            }

            $guestClient = $booking->clients->firstWhere('id', $guest->booking_client_id);

            if (!$guestClient) {
                return;
            }

            $clientBreakdown = $booking->breakdownClients(collect([$guestClient]))->first();
            $guestBreakdown = $clientBreakdown->guests->firstWhere('id', $guest->id);

            if ($guest->insurance && $guestBreakdown && $guestBreakdown->insuranceRate && $guestBreakdown->insuranceRate->rate > 0) {
                $traveInsuranceDetails[] = [
                    'amount' => $guestBreakdown->insuranceRate->rate,
                    'description' => "NON REFUNDABLE TRAVEL INSURANCE",
                    'clientId' => $guestClient->id,
                    'clientName' => $guestClient->name
                ];
            }
        });

        return [
            'accommodationCharges' => $accommodationCharges,
            'traveInsuranceDetails' => $traveInsuranceDetails,
            'travelInsuranceAdditions' => $travelInsuranceAdditions,
        ];
    }

    private function checkReservationChangeViolations($booking, $request)
    {
        $group = $booking->group;
        $minNights = $group->min_nights;
        $minNightsViolation = false;
        $shouldCCGroupsEmail = false;
        $messages = [];
        $adminMessages = [];
        $hasBalanceDueDatePassed = now()->gt($booking->group->balance_due_date);

        //mini-nights
        $roomArrangements = $request->input('booking.roomArrangements');
        $seenRoomDates = [];
        $totalRoomNights = 0;
        foreach ($roomArrangements as $room) {
            $dateKey = $room['dates']['start'] . '-' . $room['dates']['end'];
            if (isset($seenRoomDates[$dateKey])) {
                continue;
            }
            $seenRoomDates[$dateKey] = true;
            $checkIn = Carbon::parse($room['dates']['start']);
            $checkOut = Carbon::parse($room['dates']['end']);
            $nights = $checkIn->diffInDays($checkOut);
            $totalRoomNights += $nights;
        }

        if ($totalRoomNights < $minNights) {
            $minNightsViolation = true;
        }

        $guests = collect($request->input('guests'))->filter(fn($guest) => !$guest['deleted_at']);
        $seenGuestDates = [];
        $totalGuestNights = 0;
        foreach ($guests as $guest) {
            $dateKey = $guest['dates']['start'] . '-' . $guest['dates']['end'];
            if (isset($seenGuestDates[$dateKey])) {
                continue;
            }
            $seenGuestDates[$dateKey] = true;
            $checkIn = Carbon::parse($guest['dates']['start']);
            $checkOut = Carbon::parse($guest['dates']['end']);
            $nights = $checkIn->diffInDays($checkOut);
            $totalGuestNights += $nights;
        }

        if ($totalGuestNights < $minNights) {
            $minNightsViolation = true;
        }

        if ($minNightsViolation) {
            if ($hasBalanceDueDatePassed) {
                $messages[] = 'Your request results in reducing the total number of nights below the minimum number of nights required for the group rate! Would you like to proceed and request an exception? Please note that it may be denied, but if approved no refund will be due.';
                $adminMessages[] = 'Client has requested to reduce the total number of nights below the minimum number of nights required for the group rate after the Balance Due Date. Exception approval required. No refund will be due if approved.';
            } else {
                $messages[] = 'Your request results in reducing the total number of nights below the minimum number of nights required for the group rate! Would you like to proceed and request an exception? Please note that it may be denied.';
                $adminMessages[] = 'Client has requested to reduce the total number of nights below the minimum number of nights required for the group rate. Exception approval required.';
            }
        }
        if($hasBalanceDueDatePassed){
            $bookingPreview = $this->getBookingPreview($request, $booking);
            $originalTotal = $booking->clients->reduce(function($total, $client) use ($booking) {
                    $clientTotal = $booking->getClientTotal($client);
                    return $total + $clientTotal;
                }, 0);

            $newTotal = $bookingPreview->clients->reduce(function($total, $client) use ($bookingPreview) {
                $clientTotal = $bookingPreview->getClientTotal($client);
                return $total + $clientTotal;
            }, 0);

            $hasReduction = $newTotal < $originalTotal;

            if ($hasReduction) {
                $messages[] = 'Your change request is being made after the last date for reductions/cancellations. Therefore, no refund will be due. Please confirm you would like to proceed.';
                $adminMessages[] = 'Change request was submitted after the last date for reductions/cancellations. Client has acknowledged that no refund will be due.';
            }

            $currentGuestIds = $booking->guests->pluck('id')->toArray();
            $requestGuestIds = collect($request->input('guests'))->filter(fn ($guest) => empty($guest['deleted_at']))->pluck('id')->filter()->toArray();
            $removedGuestIds = array_diff($currentGuestIds, $requestGuestIds);

            foreach ($removedGuestIds as $removedGuestId) {
                $guest = $booking->guests->firstWhere('id', $removedGuestId);

                if ($guest) {
                    if ($guest->insurance) {
                        $messages[] = 'Your request to remove a guest from your room is being made after the last date for reductions/cancellations. This guest had travel insurance and will need to file a claim for reimbursement. Please confirm you would like to proceed and you will receive an email with instructions on how to file a claim.';
                        $adminMessages[] = 'Client has requested to remove a guest after the last date for reductions/cancellations. The removed guest has travel insurance and will need to file a claim for reimbursement.';
                        $shouldCCGroupsEmail = true;
                    } else {
                        $messages[] = 'Your request to remove a guest from your room is being made after the last date for reductions/cancellations. This guest does not have travel insurance. Therefore, no refund will be due. Please confirm you would like to proceed.';
                        $adminMessages[] = 'Client has requested to remove a guest after the last date for reductions/cancellations. The removed guest does not have travel insurance. Client has acknowledged that no refund will be due.';
                    }
                }
            }

        } else {
            $penalty = $this->calculateRemovedGuestPenalties($booking, $request);
            $totalPenaltyAmount = collect($penalty['traveInsuranceDetails'])->sum('amount');
            $hasReduction = $totalPenaltyAmount > 0;

            if($hasReduction) {
                $messages[] = 'Your request will result in reduction/cancellation of travel insurance. Travel insurance is non refundable and you will need to file a claim for reimbursement. Please confirm you would like to proceed and you will receive an email with instructions on how to file a claim.';
                $adminMessages[] = 'Client has requested a reduction/cancellation that will reduce travel insurance. Travel insurance is non-refundable and client will need to file a claim for reimbursement.';
            }
        }

        return [
            'minNightsViolation' => $minNightsViolation,
            'messages' => array_unique($messages),
            'adminMessages' => array_unique($adminMessages),
            'shouldCCGroupsEmail' => $shouldCCGroupsEmail,
        ];
    }

    protected function sendPaymentNotificationsToOtherClients($booking, $otherClientsPayments)
    {
        $courtesyCredits = [];

        if (empty($otherClientsPayments)) {
            return $courtesyCredits;
        }

        foreach ($otherClientsPayments as $clientPayment) {
            $clientId = $clientPayment['clientId'];
            $bookingClient = $booking->clients()->where('id', $clientId)->first();

            if (!$bookingClient || !$bookingClient->client) {
                continue;
            }

            $amountToPay = $clientPayment['amountToPay'];

            if ($amountToPay < 0) {
                $courtesyCredits[] = [
                    'clientId' => $clientId,
                    'amount' => $amountToPay,
                ];
            }

            try {
                $bookingClient->client->notify(new ClientPaymentRequired(
                    $bookingClient,
                    [
                        'amountToPay' => $clientPayment['amountToPay'],
                    ],
                    'guest change request'
                ));
            } catch (\Exception $e) {
                continue;
            }
        }

        return $courtesyCredits;
    }
}
