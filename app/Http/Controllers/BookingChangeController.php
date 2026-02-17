<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Group;
use App\Models\BookingRoomBlock;
use App\Models\Extra;
use App\Models\GroupAirport;
use App\Models\Guest;
use App\Models\Payment;
use Carbon\Carbon;
use App\Models\Client;
use App\Models\BookingClient;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;

class BookingChangeController extends Controller
{
    public function index(Group $group, Booking $booking)
    {
        $this->authorize('confirmChanges', $booking);

        $before = $booking->trackedChanges()->whereNotNull('confirmed_at')->latest('confirmed_at')->first();
        $after = $booking->trackedChanges()->whereNull('confirmed_at')->first();

        if (is_null($before) || is_null($after)) {
            return response()->json([]);
        }

        $beforeBooking = $before->snapshot;
        $afterBooking = $after->snapshot;

        $beforeRoomBlocks = collect($beforeBooking['roomBlocks'] ?? []);
        $afterRoomBlocks = collect($afterBooking['roomBlocks'] ?? []);

        $beforeClients = collect($beforeBooking['clients'] ?? []);
        $afterClients = collect($afterBooking['clients'] ?? []);
        $clientIds = $beforeClients->pluck('id')->merge($afterClients->pluck('id'))->unique();

        $changes = [
            'Room Block' => [
                'before' => $beforeRoomBlocks->map(fn($block) => "{$block['room']['name']}")->join('<br><br> '),
                'after' => $afterRoomBlocks->map(fn($block) => "{$block['room']['name']}")->join('<br><br> '),
            ],
            'Bedding' => [
                'before' => $beforeRoomBlocks->map(fn($block) => "{$block['pivot']['bed']}" . ($beforeRoomBlocks->count() > 1 ? " - {$block['room']['name']}" : ''))->join('<br><br> '),
                'after' => $afterRoomBlocks->map(fn($block) => "{$block['pivot']['bed']}" . ($afterRoomBlocks->count() > 1 ? " - {$block['room']['name']}" : ''))->join('<br><br> '),
            ],
            'Check In' => [
                'before' => $beforeRoomBlocks->map(fn($block) => "{$block['pivot']['check_in']}" . ($beforeRoomBlocks->count() > 1 ? " - {$block['room']['name']}" : ''))->join('<br><br> '),
                'after' => $afterRoomBlocks->map(fn($block) => "{$block['pivot']['check_in']}" . ($afterRoomBlocks->count() > 1 ? " - {$block['room']['name']}" : ''))->join('<br><br> '),
            ],
            'Check Out' => [
                'before' => $beforeRoomBlocks->map(fn($block) => "{$block['pivot']['check_out']}" . ($beforeRoomBlocks->count() > 1 ? " - {$block['room']['name']}" : ''))->join('<br><br> '),
                'after' => $afterRoomBlocks->map(fn($block) => "{$block['pivot']['check_out']}" . ($afterRoomBlocks->count() > 1 ? " - {$block['room']['name']}" : ''))->join('<br><br> '),
            ],
            'Special Requests' => [
                'before' => $beforeBooking['special_requests'],
                'after' => $afterBooking['special_requests'],
            ],
            'Clients' => (object)$clientIds->mapWithKeys(function ($clientId) use ($beforeClients, $afterClients) {
                $beforeClient = $beforeClients->firstWhere('id', $clientId) ?? [];
                $afterClient = $afterClients->firstWhere('id', $clientId) ?? [];

                $clientKey = empty($afterClient)
                    ? $beforeClient['first_name'] . ' ' . $beforeClient['last_name']
                    : $afterClient['first_name'] . ' ' . $afterClient['last_name'];

                $beforeExtras = collect($beforeClient['extras'] ?? []);
                $afterExtras = collect($afterClient['extras'] ?? []);
                $extraIds = $beforeExtras->pluck('id')->merge($afterExtras->pluck('id'))->unique();

                $beforeGuests = collect($beforeClient['guests'] ?? []);
                $afterGuests = collect($afterClient['guests'] ?? []);
                $guestIds = $beforeGuests->pluck('id')->merge($afterGuests->pluck('id'))->unique();

                return [
                    $clientKey => [
                        'Extras' => (object)$extraIds->mapWithKeys(function ($extraId) use ($beforeExtras, $afterExtras) {
                            $beforeExtra = $beforeExtras->firstWhere('id', $extraId) ?? [];
                            $afterExtra = $afterExtras->firstWhere('id', $extraId) ?? [];

                            $extraKey = empty($afterExtra)
                                ? $beforeExtra['description']
                                : $afterExtra['description'];

                            return [
                                $extraKey => [
                                    'Price' => [
                                        'before' => $beforeExtra['price'] ?? null,
                                        'after' => $afterExtra['price'] ?? null,
                                    ],
                                    'Quantity' => [
                                        'before' => $beforeExtra['quantity'] ?? null,
                                        'after' => $afterExtra['quantity'] ?? null,
                                    ],
                                ],
                            ];
                        })->toArray(),
                        'Guests' => (object)$guestIds->mapWithKeys(function ($guestId) use ($beforeGuests, $afterGuests) {
                            $beforeGuest = $beforeGuests->firstWhere('id', $guestId) ?? [];
                            $afterGuest = $afterGuests->firstWhere('id', $guestId) ?? [];

                            $guestKey = empty($afterGuest)
                                ? $beforeGuest['first_name'] . ' ' . $beforeGuest['last_name']
                                : $afterGuest['first_name'] . ' ' . $afterGuest['last_name'];

                            $beforeInsurance = !isset($beforeGuest['id'])
                                ? null
                                : ($beforeGuest['insurance'] === null
                                    ? 'Pending'
                                    : ($beforeGuest['insurance'] ? 'Yes' : 'No'));

                            $afterInsurance = !isset($afterGuest['id'])
                                ? null
                                : ($afterGuest['insurance'] === null
                                    ? 'Pending'
                                    : ($afterGuest['insurance'] ? 'Yes' : 'No'));

                            $beforeTransportation = !isset($beforeGuest['id'])
                                ? null
                                : ($beforeGuest['transportation'] === null
                                    ? ''
                                    : ($beforeGuest['transportation'] ? 'Yes' : 'No'));

                            $afterTransportation = !isset($afterGuest['id'])
                                ? null
                                : ($afterGuest['transportation'] === null
                                    ? ''
                                    : ($afterGuest['transportation'] ? 'Yes' : 'No'));

                            $beforeTransportationType = !isset($beforeGuest['id'])
                                ? null
                                : match ($beforeGuest['transportation_type']) {
                                    1 => 'Round Trip',
                                    2 => 'One Way Airport to Hotel',
                                    3 => 'One Way Hotel to Airport',
                                    default => ''
                                };

                            $afterTransportationType = !isset($afterGuest['id'])
                                ? null
                                : match ($afterGuest['transportation_type']) {
                                    1 => 'Round Trip',
                                    2 => 'One Way Airport to Hotel',
                                    3 => 'One Way Hotel to Airport',
                                    default => ''
                                };

                            $beforeCustomAirport = !isset($beforeGuest['id'])
                                ? null
                                : (empty($beforeGuest['custom_group_airport'])
                                    ? ''
                                    : optional(GroupAirport::find($beforeGuest['custom_group_airport']))->airport?->airport_code);

                            $afterCustomAirport = !isset($afterGuest['id'])
                                ? null
                                : (empty($afterGuest['custom_group_airport'])
                                    ? ''
                                    : optional(GroupAirport::find($afterGuest['custom_group_airport']))->airport?->airport_code);

                            return [
                                $guestKey => [
                                    'First Name' => [
                                        'before' => $beforeGuest['first_name'] ?? null,
                                        'after' => $afterGuest['first_name'] ?? null,
                                    ],
                                    'Last Name' => [
                                        'before' => $beforeGuest['last_name'] ?? null,
                                        'after' => $afterGuest['last_name'] ?? null,
                                    ],
                                    'Gender' => [
                                        'before' => $beforeGuest['gender'] ?? null,
                                        'after' => $afterGuest['gender'] ?? null,
                                    ],
                                    'Birth Date' => [
                                        'before' => $beforeGuest['birth_date'] ?? null,
                                        'after' => $afterGuest['birth_date'] ?? null,
                                    ],
                                    'Check In' => [
                                        'before' => $beforeGuest['check_in'] ?? null,
                                        'after' => $afterGuest['check_in'] ?? null,
                                    ],
                                    'Check Out' => [
                                        'before' => $beforeGuest['check_out'] ?? null,
                                        'after' => $afterGuest['check_out'] ?? null,
                                    ],
                                    'Insurance' => [
                                        'before' => $beforeInsurance,
                                        'after' => $afterInsurance,
                                    ],
                                    'Transportation' => [
                                        'before' => $beforeTransportation,
                                        'after' => $afterTransportation,
                                    ],
                                    'Transportation Type' => [
                                        'before' => $beforeTransportationType,
                                        'after' => $afterTransportationType,
                                    ],
                                    'Custom Airport' => [
                                        'before' => $beforeCustomAirport,
                                        'after' => $afterCustomAirport,
                                    ],
                                    'Trashed' => [
                                        'before' => isset($beforeGuest['id']) ? ($beforeGuest['deleted_at'] ? 'Yes' : 'No') : null,
                                        'after' => isset($afterGuest['id']) ? ($afterGuest['deleted_at'] ? 'Yes' : 'No') : null,
                                    ],
                                ]
                            ];
                        })->toArray(),
                    ],
                ];
            })->toArray(),
        ];

        return response()->json($changes);
    }

    public function confirm(Group $group, Booking $booking, Request $request)
    {
        $this->authorize('confirmChanges', $booking);

        $booking->trackedChanges()->whereNull('confirmed_at')->update([
            'confirmed_at' => now(),
        ]);

        $guestChangeToConfirm = $booking->guestChanges()
            ->whereNull('admin_confirmed_at')
            ->whereNull('admin_cancelled_at')
            ->whereNotNull('confirmed_at')
            ->whereNull('deleted_at')
            ->with(['bookingClient.client'])
            ->get();

        if ($guestChangeToConfirm->isNotEmpty()) {
            $guestChangeToConfirm->each(function ($guestChange) {
                $guestChange->update([
                    'admin_confirmed_at' => now(),
                ]);
            });
        }

        if($request->sendEmail){
            $booking->clients()->with('client')->get()->each(function ($bookingClient) {
                if ($bookingClient->client) {
                    $bookingClient->client->notify(new \App\Notifications\AdminConfirmGuestChanges($bookingClient));
                }
            });
        }

        return response()->json();
    }

    public function revert(Group $group, Booking $booking, Request $request)
    {
        $this->authorize('confirmChanges', $booking);

        // Check if both previous and current state exist
        $before = $booking->trackedChanges()->whereNotNull('confirmed_at')->latest('confirmed_at')->first();
        $after = $booking->trackedChanges()->whereNull('confirmed_at')->first();

        if (is_null($before) || is_null($after)) {
            return response()->json(['message' => 'The system is unable to revert these changes.'], 422);
        }

        // Get previous and current booking and client snapshots
        $beforeBooking = $before->snapshot;
        $afterBooking = $after->snapshot;
        $beforeClients = collect($beforeBooking['clients']);
        $afterClients = collect($afterBooking['clients']);

        // Get previous and current clients and check whether any clients were deleted because data lost in this case cannot be restored automatically.
        $deletedClientIds = $beforeClients->pluck('id')->diff($afterClients->pluck('id'));

        if ($deletedClientIds->isNotEmpty()) {
            return response()->json(['message' => 'Client(s) were deleted and cannot be automatically restored. Please revert manually.'], status: 422);
        }

        // Restore booking room blocks
        BookingRoomBlock::where('booking_id', $booking->id)->delete();

        foreach ($beforeBooking['roomBlocks'] as $roomBlock) {
            BookingRoomBlock::create([
                'booking_id' => $booking->id,
                'room_block_id' => $roomBlock['id'],
                'bed' => $roomBlock['pivot']['bed'],
                'check_in' => $roomBlock['pivot']['check_in'],
                'check_out' => $roomBlock['pivot']['check_out'],
            ]);
        }

        // Restore client extras
        foreach ($afterClients as $afterClient) {
            $clientId = $afterClient['id'];
            $beforeClient = $beforeClients->firstWhere('id', $clientId);

            // If any client is new and was not present in the previous state, delete all extras and move on to the next client
            if (!$beforeClient) {
                Extra::where('booking_client_id', $clientId)->delete();

                continue;
            }

            // For those clients that were present in both states, restore their extras
            $afterExtras = collect($afterClient['extras']);
            $beforeExtras = collect($beforeClient['extras']);

            $extrasToDelete = $afterExtras->reject(fn ($extra) =>
                $beforeExtras->contains('id', $extra['id'])
            );

            Extra::whereIn('id', $extrasToDelete->pluck('id'))->delete();

            $extrasToUpdate = $afterExtras->filter(fn ($extra) =>
                $beforeExtras->contains('id', $extra['id'])
            );

            foreach ($extrasToUpdate as $extra) {
                $original = $beforeExtras->firstWhere('id', $extra['id']);

                Extra::where('id', $extra['id'])->update([
                    'booking_client_id' => $original['booking_client_id'],
                    'description' => $original['description'],
                    'price' => $original['price'],
                    'quantity' => $original['quantity'],
                    'created_at' => $original['created_at'],
                    'updated_at' => $original['updated_at'],
                ]);
            }

            $extrasToRestore = $beforeExtras->reject(fn ($extra) =>
                $afterExtras->contains('id', $extra['id'])
            );

            foreach ($extrasToRestore as $extra) {
                Extra::create(Arr::except($extra, ['id']));
            }
        }

        // Restore guests
        $afterGuests = $afterClients->flatMap(fn ($c) => $c['guests']);
        $beforeGuests = $beforeClients->flatMap(fn ($c) => $c['guests']);

        $guestsToDelete = $afterGuests->reject(fn ($guest) =>
            $beforeGuests->contains('id', $guest['id'])
        );

        Guest::whereIn('id', $guestsToDelete->pluck('id'))->forceDelete();

        $guestsToUpdate = $afterGuests->filter(fn ($guest) =>
            $beforeGuests->contains('id', $guest['id'])
        );

        foreach ($guestsToUpdate as $guest) {
            $original = $beforeGuests->firstWhere('id', $guest['id']);

            $data = [
                'booking_client_id' => $original['booking_client_id'],
                'first_name' => $original['first_name'],
                'last_name' => $original['last_name'],
                'gender' => $original['gender'],
                'birth_date' => $original['birth_date'],
                'check_in' => $original['check_in'],
                'check_out' => $original['check_out'],
                'insurance' => $original['insurance'],
                'transportation' => $original['transportation'],
                'transportation_type' => $original['transportation_type'],
                'custom_group_airport' => $original['custom_group_airport'],
                'created_at' => $original['created_at'],
                'updated_at' => $original['updated_at'],
                'deleted_at' => $original['deleted_at']
            ];

            if ($guest['transportation'] != $original['transportation']) {
                $data['departure_pickup_time'] = $original['departure_pickup_time'];
            }

            Guest::withTrashed()->where('id', $guest['id'])->update($data);
        }

        $guestsToRestore = $beforeGuests->reject(fn ($guest) =>
            $afterGuests->contains('id', $guest['id'])
        );

        foreach ($guestsToRestore as $guest) {
            Guest::create(Arr::except($guest, ['id']));
        }

        $booking->special_requests = $beforeBooking['special_requests'];
        $booking->total <= $booking->payment_total ? $booking->is_paid = true : $booking->is_paid = false;
        $booking->save();

        $booking->trackedChanges()->whereNull('confirmed_at')->delete();

        $guestChangeToCancel = $booking->guestChanges()
            ->whereNull('admin_confirmed_at')
            ->whereNull('admin_cancelled_at')
            ->whereNotNull('confirmed_at')
            ->whereNull('deleted_at')
            ->with(['bookingClient.client'])
            ->get();

        if ($guestChangeToCancel->isNotEmpty()) {
            $guestChangeToCancel->each(function ($guestChange) use ($booking) {
                $guestChange->update([
                    'admin_cancelled_at' => now(),
                ]);

                if(!empty($guestChange->payment_details)) {
                    $this->removePaymentChangesToBooking(json_decode($guestChange->payment_details, true));
                }

                if(isset($guestChange->snapshot['newSeperateClients']) && !empty($guestChange->snapshot['newSeperateClients'])) {
                    $this->removeNewSeperateClientsToBooking($booking, $guestChange->snapshot);
                }
            });
        }

        if($request->sendEmail){
            $booking->clients()->with('client')->get()->each(function ($bookingClient) {
                if ($bookingClient->client) {
                    $bookingClient->client->notify(new \App\Notifications\AdminCancelGuestChanges($bookingClient));
                }
            });
        }

        return response()->json();
    }

    public function guestChanges(Group $group, Booking $booking, $guestChangeId = null)
    {
        if ($guestChangeId) {
            $after = $booking->guestChanges()->where('id', $guestChangeId)->whereNull('deleted_at')->first();
        }

        if (!$after) {
            return response()->json(['message' => 'No pending guest changes found.'], 404);
        }

        $booking->load(['clients.client', 'clients.guests' => function($query) {
            $query->withTrashed();
        }, 'roomBlocks.room.hotel']);

        $beforeData = [
            'special_requests' => $booking->special_requests,
            'guests' => $booking->clients->flatMap->guests->map(function ($guest) {
                return [
                    'id' => $guest->id,
                    'first_name' => $guest->first_name,
                    'last_name' => $guest->last_name,
                    'gender' => $guest->gender,
                    'birth_date' => $guest->birth_date,
                    'check_in' => $guest->check_in,
                    'check_out' => $guest->check_out,
                    'insurance' => $guest->insurance,
                    'transportation' => $guest->transportation,
                    'transportation_type' => $guest->transportation_type,
                    'custom_group_airport' => $guest->custom_group_airport,
                    'booking_client_id' => $guest->booking_client_id,
                    'trashed' => $guest->trashed() ? 'Yes' : 'No',
                ];
            })->toArray(),
            'roomBlocks' => $booking->roomBlocks->map(function ($roomBlock) {
                return [
                    'id' => $roomBlock->id,
                    'room_block_id' => $roomBlock->room_block_id,
                    'pivot' => [
                        'bed' => $roomBlock->pivot->bed,
                        'check_in' => $roomBlock->pivot->check_in,
                        'check_out' => $roomBlock->pivot->check_out,
                        'room_block_id' => $roomBlock->room_block_id,
                    ],
                    'room' => [
                        'name' => $roomBlock->room->name,
                        'hotel' => [
                            'name' => $roomBlock->room->hotel->name,
                        ],
                    ],
                ];
            })->toArray()
        ];

        $afterData = $after->snapshot;
        $beforeGuests = collect($beforeData['guests']);

        $afterGuests = collect($afterData['clients'] ?? [])
            ->flatMap(function ($client) use ($beforeGuests) {
                return collect($client['guests'] ?? [])->map(function ($guest) use ($beforeGuests) {
                    $beforeGuest = isset($guest['id']) ? ($beforeGuests->firstWhere('id', $guest['id']) ?? []) : [];

                    return [
                        'id' => isset($guest['id']) ? $guest['id'] : null,
                        'first_name' => !$guest['deleted_at'] ? $guest['first_name'] : ($beforeGuest['first_name'] ?? ''),
                        'last_name' => !$guest['deleted_at'] ? $guest['last_name'] : ($beforeGuest['last_name'] ?? ''),
                        'gender' => !$guest['deleted_at'] ? $guest['gender'] : ($beforeGuest['gender'] ?? ''),
                        'birth_date' => !$guest['deleted_at'] ? $guest['birth_date'] : ($beforeGuest['birth_date'] ?? ''),
                        'check_in' => !$guest['deleted_at'] ? $guest['check_in'] : ($beforeGuest['check_in'] ?? ''),
                        'check_out' => !$guest['deleted_at'] ? $guest['check_out'] : ($beforeGuest['check_out'] ?? ''),
                        'insurance' => !$guest['deleted_at'] ? $guest['insurance'] : ($beforeGuest['insurance'] ?? null),
                        'transportation' => !$guest['deleted_at'] ? $guest['transportation'] : ($beforeGuest['transportation'] ?? ''),
                        'transportation_type' => !$guest['deleted_at'] ? $guest['transportation_type'] : ($beforeGuest['transportation_type'] ?? ''),
                        'custom_group_airport' => !$guest['deleted_at'] ? $guest['custom_group_airport'] : ($beforeGuest['custom_group_airport'] ?? ''),
                        'booking_client_id' => !$guest['deleted_at'] ? $guest['booking_client_id'] : ($beforeGuest['booking_client_id'] ?? ''),
                        'trashed' => isset($guest['id']) ? (isset($guest['deleted_at']) && $guest['deleted_at'] !== null ? 'Yes' : 'No') : null,
                    ];
                });
            });

        $normalizedAfterData = [
            'special_requests' => $afterData['special_requests'] ?? null,
            'guests' => $afterGuests->toArray(),
            'roomBlocks' => $afterData['roomBlocks'] ?? []
        ];

        $beforeRoomBlocks = collect($beforeData['roomBlocks'] ?? []);
        $afterRoomBlocks = collect($normalizedAfterData['roomBlocks'] ?? []);

        $beforeClientsSnapshot = $booking->clients->map(function ($bookingClient) {
            if ($bookingClient->client) {
                return [
                    'id' => $bookingClient->id,
                    'first_name' => $bookingClient->client->first_name,
                    'last_name' => $bookingClient->client->last_name,
                ];
            }
            return null;
        })->filter();
        $afterClientsSnapshot = collect($afterData['clients'] ?? []);

        $beforeClients = collect($beforeData['guests'])->groupBy('booking_client_id')->map(function ($guests) {
            return [
                'id' => $guests->first()['booking_client_id'],
                'guests' => $guests->toArray()
            ];
        });

        $afterClients = collect($normalizedAfterData['guests'])->groupBy('booking_client_id')->map(function ($guests) {
            return [
                'id' => $guests->first()['booking_client_id'],
                'guests' => $guests->toArray()
            ];
        });

        $clientIds = $beforeClientsSnapshot->pluck('id')
            ->merge($afterClientsSnapshot->pluck('id'))
            ->merge($beforeClients->pluck('id'))
            ->merge($afterClients->pluck('id'))
            ->unique();

        $changes = [
            'Room Block' => [
                'before' => $beforeRoomBlocks->map(fn($block) => "{$block['room']['name']}")->join('<br><br> '),
                'after' => $afterRoomBlocks->map(fn($block) => "{$block['room']['name']}")->join('<br><br> '),
            ],
            'Bedding' => [
                'before' => $beforeRoomBlocks->map(fn($block) => "{$block['pivot']['bed']}" . ($beforeRoomBlocks->count() > 1 ? " - {$block['room']['name']}" : ''))->join('<br><br> '),
                'after' => $afterRoomBlocks->map(fn($block) => "{$block['pivot']['bed']}" . ($afterRoomBlocks->count() > 1 ? " - {$block['room']['name']}" : ''))->join('<br><br> '),
            ],
            'Check In' => [
                'before' => $beforeRoomBlocks->map(fn($block) => date('Y-m-d', strtotime($block['pivot']['check_in'] ?? '')) . ($beforeRoomBlocks->count() > 1 ? " - {$block['room']['name']}" : ''))->join('<br><br> '),
                'after' => $afterRoomBlocks->map(fn($block) => date('Y-m-d', strtotime($block['pivot']['check_in'] ?? '')) . ($afterRoomBlocks->count() > 1 ? " - {$block['room']['name']}" : ''))->join('<br><br> '),
            ],
            'Check Out' => [
                'before' => $beforeRoomBlocks->map(fn($block) => date('Y-m-d', strtotime($block['pivot']['check_out'] ?? '')) . ($beforeRoomBlocks->count() > 1 ? " - {$block['room']['name']}" : ''))->join('<br><br> '),
                'after' => $afterRoomBlocks->map(fn($block) => date('Y-m-d', strtotime($block['pivot']['check_out'] ?? '')) . ($afterRoomBlocks->count() > 1 ? " - {$block['room']['name']}" : ''))->join('<br><br> '),
            ],
            'Special Requests' => [
                'before' => $beforeData['special_requests'] ?: null,
                'after' => $normalizedAfterData['special_requests'] ?: null,
            ],
            'Clients' => (object)$clientIds->mapWithKeys(function ($clientId) use ($beforeClients, $afterClients, $beforeData, $normalizedAfterData, $beforeClientsSnapshot, $afterClientsSnapshot) {
                $beforeClient = $beforeClients->firstWhere('id', $clientId) ?? [];
                $afterClient = $afterClients->firstWhere('id', $clientId) ?? [];

                $beforeClientSnap = $beforeClientsSnapshot->firstWhere('id', $clientId);
                $afterClientSnap = $afterClientsSnapshot->firstWhere('id', $clientId);

                if (!$afterClientSnap && is_string($clientId) && filter_var($clientId, FILTER_VALIDATE_EMAIL)) {
                    $afterClientSnap = $afterClientsSnapshot->first(function ($client) use ($clientId) {
                        return !isset($client['id']) && isset($client['guests']) &&
                               collect($client['guests'])->contains('booking_client_id', $clientId);
                    });
                }

                if ($afterClientSnap) {
                    $clientKey = $afterClientSnap['first_name'] . ' ' . $afterClientSnap['last_name'];
                } elseif ($beforeClientSnap) {
                    $clientKey = $beforeClientSnap['first_name'] . ' ' . $beforeClientSnap['last_name'];
                } else{
                    return [];
                }

                $beforeGuests = collect($beforeClient['guests'] ?? []);
                $afterGuests = collect($afterClient['guests'] ?? []);
                $allIds = $beforeGuests->pluck('id')->merge($afterGuests->pluck('id'));
                $guestIds = $allIds->filter()->unique()->merge($allIds->filter(fn($id) => is_null($id)));

                $guestIds = $guestIds->filter(function($guestId) use ($clientId, $normalizedAfterData, $beforeData) {
                    if (is_null($guestId)) return true;

                    $globalBeforeGuest = collect($beforeData['guests'])->firstWhere('id', $guestId);
                    $globalAfterGuest = collect($normalizedAfterData['guests'])->firstWhere('id', $guestId);

                    $inBeforeClient = $globalBeforeGuest && $globalBeforeGuest['booking_client_id'] == $clientId;
                    $inAfterClient = $globalAfterGuest && $globalAfterGuest['booking_client_id'] == $clientId;

                    return $inBeforeClient || $inAfterClient;
                });
                $newGuestIndex = 0;
                return [
                    $clientKey => [
                        'Guests' => (object)$guestIds->mapWithKeys(function ($guestId) use ($beforeGuests, $afterGuests, $beforeData, $normalizedAfterData, &$newGuestIndex) {

                            if(is_null($guestId)){
                                $beforeGuest = [];
                                $afterGuest = $afterGuests->filter(fn($g) => !isset($g['id']) || is_null($g['id']))->values()->get($newGuestIndex);
                                $newGuestIndex++;
                            } else {
                                $beforeGuest = $beforeGuests->firstWhere('id', $guestId) ?? [];
                                $afterGuest = $afterGuests->firstWhere('id', $guestId) ?? [];
                            }

                            $guestKey = empty($afterGuest)
                                ? (isset($beforeGuest['first_name']) ? $beforeGuest['first_name'] : '') . ' ' . (isset($beforeGuest['last_name']) ? $beforeGuest['last_name'] : '')
                                : (isset($afterGuest['first_name']) ? $afterGuest['first_name'] : '') . ' ' . (isset($afterGuest['last_name']) ? $afterGuest['last_name'] : '');

                            $beforeInsurance = !isset($beforeGuest['id'])
                                ? null
                                : (!isset($beforeGuest['insurance']) || $beforeGuest['insurance'] === null
                                    ? 'Pending'
                                    : ($beforeGuest['insurance'] ? 'Yes' : 'No'));

                            $afterInsurance = !isset($afterGuest['id'])
                                ? (!isset($afterGuest['insurance']) || $afterGuest['insurance'] === null ? (isset($afterGuest['first_name']) ? 'Pending' : null) : ($afterGuest['insurance'] ? 'Yes' : 'No'))
                                : (!isset($afterGuest['insurance']) || $afterGuest['insurance'] === null
                                    ? 'Pending'
                                    : ($afterGuest['insurance'] ? 'Yes' : 'No'));

                            $beforeTransportation = !isset($beforeGuest['id'])
                                ? null
                                : (!isset($beforeGuest['transportation']) || $beforeGuest['transportation'] === null
                                    ? null
                                    : ($beforeGuest['transportation'] ? 'Yes' : 'No'));

                            $afterTransportation = !isset($afterGuest['id'])
                                ? (!isset($afterGuest['transportation']) || $afterGuest['transportation'] === null ? null : ($afterGuest['transportation'] ? 'Yes' : 'No'))
                                : (!isset($afterGuest['transportation']) || $afterGuest['transportation'] === null
                                    ? null
                                    : ($afterGuest['transportation'] ? 'Yes' : 'No'));

                            $beforeTransportationType = !isset($beforeGuest['id'])
                                ? null
                                : ((!isset($beforeGuest['transportation']) || !$beforeGuest['transportation']) ? '' : (!isset($beforeGuest['transportation_type']) ? '' : match ($beforeGuest['transportation_type']) {
                                    1 => 'Round Trip',
                                    2 => 'One Way Airport to Hotel',
                                    3 => 'One Way Hotel to Airport',
                                    default => ''
                                }));

                            $afterTransportationType = !isset($afterGuest['id'])
                                ? ((!isset($afterGuest['transportation']) || !$afterGuest['transportation']) ? null : (!isset($afterGuest['transportation_type']) ? null : match ($afterGuest['transportation_type']) {
                                    1 => 'Round Trip',
                                    2 => 'One Way Airport to Hotel',
                                    3 => 'One Way Hotel to Airport',
                                    default => ''
                                }))
                                : ((!isset($afterGuest['transportation']) || !$afterGuest['transportation']) ? '' : (!isset($afterGuest['transportation_type']) ? '' : match ($afterGuest['transportation_type']) {
                                    1 => 'Round Trip',
                                    2 => 'One Way Airport to Hotel',
                                    3 => 'One Way Hotel to Airport',
                                    default => ''
                                }));

                            $beforeCustomAirport = !isset($beforeGuest['id'])
                                ? null
                                : ((!isset($beforeGuest['transportation']) || !$beforeGuest['transportation']) ? '' : (!isset($beforeGuest['custom_group_airport']) || empty($beforeGuest['custom_group_airport'])
                                    ? ''
                                    : optional(GroupAirport::find($beforeGuest['custom_group_airport']))->airport?->airport_code));

                            $afterCustomAirport = !isset($afterGuest['id'])
                                ? ((!isset($afterGuest['transportation']) || !$afterGuest['transportation']) ? null : (!isset($afterGuest['custom_group_airport']) || empty($afterGuest['custom_group_airport'])
                                    ? null
                                    : optional(GroupAirport::find($afterGuest['custom_group_airport']))->airport?->airport_code))
                                : ((!isset($afterGuest['transportation']) || !$afterGuest['transportation']) ? '' : (!isset($afterGuest['custom_group_airport']) || empty($afterGuest['custom_group_airport'])
                                    ? ''
                                    : optional(GroupAirport::find($afterGuest['custom_group_airport']))->airport?->airport_code));

                            return [
                                $guestKey => [
                                    'First Name' => [
                                        'before' => isset($beforeGuest['first_name']) ? $beforeGuest['first_name'] : null,
                                        'after' => isset($afterGuest['first_name']) ? $afterGuest['first_name'] : null,
                                    ],
                                    'Last Name' => [
                                        'before' => isset($beforeGuest['last_name']) ? $beforeGuest['last_name'] : null,
                                        'after' => isset($afterGuest['last_name']) ? $afterGuest['last_name'] : null,
                                    ],
                                    'Gender' => [
                                        'before' => isset($beforeGuest['gender']) ? $beforeGuest['gender'] : null,
                                        'after' => isset($afterGuest['gender']) ? $afterGuest['gender'] : null,
                                    ],
                                    'Birth Date' => [
                                        'before' => isset($beforeGuest['birth_date']) ? ($beforeGuest['birth_date'] ? date('Y-m-d', strtotime($beforeGuest['birth_date'])) : null) : null,
                                        'after' => isset($afterGuest['birth_date']) ? ($afterGuest['birth_date'] ? date('Y-m-d', strtotime($afterGuest['birth_date'])) : null) : null,
                                    ],
                                    'Check In' => [
                                        'before' => isset($beforeGuest['check_in']) ? ($beforeGuest['check_in'] ? date('Y-m-d', strtotime($beforeGuest['check_in'])) : null) : null,
                                        'after' => isset($afterGuest['check_in']) ? ($afterGuest['check_in'] ? date('Y-m-d', strtotime($afterGuest['check_in'])) : null) : null,
                                    ],
                                    'Check Out' => [
                                        'before' => isset($beforeGuest['check_out']) ? ($beforeGuest['check_out'] ? date('Y-m-d', strtotime($beforeGuest['check_out'])) : null) : null,
                                        'after' => isset($afterGuest['check_out']) ? ($afterGuest['check_out'] ? date('Y-m-d', strtotime($afterGuest['check_out'])) : null) : null,
                                    ],
                                    'Insurance' => [
                                        'before' => $beforeInsurance,
                                        'after' => $afterInsurance,
                                    ],
                                    'Transportation' => [
                                        'before' => $beforeTransportation,
                                        'after' => $afterTransportation,
                                    ],
                                    'Transportation Type' => [
                                        'before' => $beforeTransportationType,
                                        'after' => $afterTransportationType,
                                    ],
                                    'Custom Airport' => [
                                        'before' => $beforeCustomAirport,
                                        'after' => $afterCustomAirport,
                                    ],
                                    'Trashed' => [
                                        'before' => isset($beforeGuest['id']) ? ($beforeGuest['trashed'] === 'Yes' ? 'Yes' : 'No') : null,
                                        'after' => isset($afterGuest['id']) ? ($afterGuest['trashed'] === 'Yes' ? 'Yes' : 'No') : (isset($beforeGuest['id']) ? null : 'No'),
                                    ],
                                ]
                            ];
                        })->toArray(),
                    ],
                ];
            })->toArray(),
        ];

        return response()->json($changes);
    }

    public function confirmGuestChanges(Group $group, Booking $booking, $guestChangeId = null)
    {
        $this->authorize('update', $booking);

        if ($guestChangeId) {
            $guestChange = $booking->guestChanges()->with('bookingClient.client', 'bookingClient.booking.group')->where('id', $guestChangeId)->whereNull('deleted_at')->first();

            if (!$guestChange) {
                return response()->json(['message' => 'No pending guest changes found.'], 404);
            }

            $booking->load('group');
            $this->applyGuestChangesToBooking($booking, $guestChange->snapshot);

            $guestChange->update(['confirmed_at' => now(), 'confirmed_by' => auth()->user()->id]);

            if($guestChange->bookingClient) {
                event(new \App\Events\GuestChangeApproved($guestChange->bookingClient));
            }
        }

        return response()->json(['message' => 'Guest changes confirmed successfully.']);
    }

    public function revertGuestChanges(Group $group, Booking $booking, $guestChangeId = null)
    {
        $this->authorize('update', $booking);

        if ($guestChangeId) {
            $guestChange = $booking->guestChanges()->with('bookingClient.client', 'bookingClient.booking.group')->where('id', $guestChangeId)->whereNull('deleted_at')->first();

            if (!$guestChange) {
                return response()->json(['message' => 'No pending guest changes found.'], 404);
            }

            if(!empty($guestChange->payment_details)) {
                $this->removePaymentChangesToBooking(json_decode($guestChange->payment_details, true));
            }

            $guestChange->delete();

            if($guestChange->bookingClient) {
                event(new \App\Events\GuestChangeCancelled($guestChange->bookingClient));
            }
        }

        return response()->json(['message' => 'Guest changes reverted successfully.']);
    }

    private function applyGuestChangesToBooking(Booking $booking, $snapshot)
    {

        if (isset($snapshot['roomBlocks'])) {
            $existingRoomBlocks = $booking->roomBlocks;

            $snapshotRoomBlockIds = collect($snapshot['roomBlocks'])->map(function ($roomBlockData) {
                return $roomBlockData['room_block_id'];
            })->filter()->toArray();

            foreach ($snapshot['roomBlocks'] as $roomBlockData) {
                $roomBlockId = $roomBlockData['room_block_id'];

                if ($roomBlockId) {
                    $existingRoomBlock = $existingRoomBlocks->first(function($block) use ($roomBlockId) {
                        return $block->pivot->room_block_id == $roomBlockId;
                    });

                    if ($existingRoomBlock) {
                        $existingRoomBlock->pivot->update([
                            'bed' => $roomBlockData['pivot']['bed'] ?? $existingRoomBlock->pivot->bed,
                            'check_in' => $roomBlockData['pivot']['check_in'] ?? $existingRoomBlock->pivot->check_in,
                            'check_out' => $roomBlockData['pivot']['check_out'] ?? $existingRoomBlock->pivot->check_out,
                        ]);
                    } else {
                        BookingRoomBlock::create([
                            'booking_id' => $booking->id,
                            'room_block_id' => $roomBlockId,
                            'bed' => $roomBlockData['pivot']['bed'] ?? null,
                            'check_in' => $roomBlockData['pivot']['check_in'] ?? null,
                            'check_out' => $roomBlockData['pivot']['check_out'] ?? null,
                        ]);
                    }
                }
            }

            $existingRoomBlockIds = $existingRoomBlocks->map(function ($block) {
                return $block->pivot->room_block_id;
            })->toArray();

            $roomBlocksToDelete = array_diff($existingRoomBlockIds, $snapshotRoomBlockIds);

            if (!empty($roomBlocksToDelete)) {
                BookingRoomBlock::where('booking_id', $booking->id)
                    ->whereIn('room_block_id', $roomBlocksToDelete)
                    ->delete();
            }
        }

        if (isset($snapshot['clients'])) {
            $snapshotGuestIds = collect($snapshot['clients'])->flatMap(function ($clientData) {
                return collect($clientData['guests'] ?? [])->filter(fn($guest) => !$guest['deleted_at'])->pluck('id')->toArray();
            })->filter(function($id) { return $id !== null; })->toArray();

            $newlyCreatedGuestIds = [];
            $newClientIdMap = [];
            $newSeperateClientsMap = collect($snapshot['newSeperateClients'] ?? [])->keyBy('email');

            foreach ($snapshot['clients'] as $clientData) {
                $clientId = $clientData['id'] ?? null;

                if (!$clientId || (is_string($clientId) && str_starts_with($clientId, 'new_client_'))) {
                    $newClientEmail = $clientData['email'] ?? ($clientId ? str_replace('new_client_', '', $clientId) : null);

                    if (!$newClientEmail) {
                        $matchingNewClient = $newSeperateClientsMap->first(function($newClient) use($clientData) {
                            return ($newClient['firstName'] ?? '') === ($clientData['first_name'] ?? '') &&
                                   ($newClient['lastName'] ?? '') === ($clientData['last_name'] ?? '');
                        });
                        $newClientEmail = $matchingNewClient['email'] ?? null;
                    }

                    if (!$newClientEmail) {
                        continue;
                    }

                    $client = Client::firstOrCreate(
                        ['email' => $newClientEmail],
                        [
                            'first_name' => $clientData['first_name'],
                            'last_name' => $clientData['last_name']
                        ]
                    );

                    $bookingClient = $booking->clients()->create([
                        'client_id' => $client->id,
                        'first_name' => $clientData['first_name'],
                        'last_name' => $clientData['last_name'],
                        'telephone' => $clientData['telephone'] ?? null,
                    ]);

                    if ($clientId) {
                        $newClientIdMap[$clientId] = $bookingClient->id;
                    }
                    if ($newClientEmail) {
                        $newClientIdMap[$newClientEmail] = $bookingClient->id;
                    }

                    $client->notify(new \App\Notifications\BookingSubmitted($booking));
                    if (!$booking->group->is_fit) {
                        $client->notify(new \App\Notifications\BookingSubmittedReservationCodeSeperateInvoice($booking));
                    }

                    $clientId = $bookingClient->id;
                }

                if (!$clientId) {
                    continue;
                }

                $client = $booking->clients()->where('id', $clientId)->first();

                if ($client && isset($clientData['guests'])) {
                    foreach ($clientData['guests'] as $guestData) {
                        if ($guestData['deleted_at']) {
                            continue;
                        }

                        $guestId = $guestData['id'] ?? null;

                        if ($guestId) {
                            $guest = Guest::where('id', $guestId)->first();

                            if ($guest) {
                                $bookingClientId = $guestData['booking_client_id'] ?? $client->id;
                                if (isset($newClientIdMap[$bookingClientId])) {
                                    $bookingClientId = $newClientIdMap[$bookingClientId];
                                }

                                $guest->update([
                                    'booking_client_id' => $bookingClientId,
                                    'first_name' => $guestData['first_name'] ?? $guest->first_name,
                                    'last_name' => $guestData['last_name'] ?? $guest->last_name,
                                    'gender' => $guestData['gender'] ?? $guest->gender,
                                    'birth_date' => $guestData['birth_date'] ?? $guest->birth_date,
                                    'check_in' => $guestData['check_in'] ?? $guest->check_in,
                                    'check_out' => $guestData['check_out'] ?? $guest->check_out,
                                    'insurance' => array_key_exists('insurance', $guestData) ? $guestData['insurance'] : $guest->insurance,
                                    'transportation' => $guestData['transportation'] ?? $guest->transportation,
                                    'transportation_type' => $guestData['transportation'] ? ($guestData['transportation_type'] ?? 1) : null,
                                    'custom_group_airport' => $guestData['transportation'] ? ($guestData['custom_group_airport'] ?? $booking->group->defaultAirport()->id) : null,
                                ]);
                            }
                        } else {
                            $bookingClientId = $guestData['booking_client_id'] ?? $client->id;
                            if (isset($newClientIdMap[$bookingClientId])) {
                                $bookingClientId = $newClientIdMap[$bookingClientId];
                            }

                            $newGuest = Guest::create([
                                'booking_client_id' => $bookingClientId,
                                'first_name' => $guestData['first_name'] ?? '',
                                'last_name' => $guestData['last_name'] ?? '',
                                'gender' => $guestData['gender'] ?? '',
                                'birth_date' => $guestData['birth_date'] ?? null,
                                'check_in' => $guestData['check_in'] ?? null,
                                'check_out' => $guestData['check_out'] ?? null,
                                'insurance' => $guestData['insurance'] ?? null,
                                'transportation' => $guestData['transportation'] ?? 0,
                                'transportation_type' => $guestData['transportation'] ? ($guestData['transportation_type'] ?? 1) : null,
                                'custom_group_airport' => $guestData['transportation'] ? ($guestData['custom_group_airport'] ?? $booking->group->defaultAirport()->id) : null,
                            ]);

                            $newlyCreatedGuestIds[] = $newGuest->id;
                        }
                    }
                }
            }

            $existingGuestIds = Guest::whereHas('booking_client', function ($query) use ($booking) {
                $query->where('booking_id', $booking->id);
            })->pluck('id')->toArray();

            $guestsToDelete = array_diff($existingGuestIds, array_merge($snapshotGuestIds, $newlyCreatedGuestIds));

            if (!empty($guestsToDelete)) {
                Guest::whereIn('id', $guestsToDelete)->delete();
            }
        }

        $booking->special_requests = $snapshot['special_requests'] ?? null;
        $booking->total <= $booking->payment_total ? $booking->is_paid = true : $booking->is_paid = false;
        $booking->save();
    }

    private function removePaymentChangesToBooking($paymentDetails)
    {
        if (!$paymentDetails || !isset($paymentDetails['payment_id']) && !isset($paymentDetails['extra_id'])) {
            return;
        }

        if (isset($paymentDetails['payment_id'])) {
            $paymentIds = is_array($paymentDetails['payment_id']) ? $paymentDetails['payment_id'] : [$paymentDetails['payment_id']];
            $payment = Payment::whereIn('id', $paymentIds)->first();
            if ($payment) {
                $payment->update(['cancelled_at' => now()]);
            }
        }

        if (isset($paymentDetails['extra_id'])) {
            $extraIds = is_array($paymentDetails['extra_id']) ? $paymentDetails['extra_id'] : [$paymentDetails['extra_id']];
            Extra::whereIn('id', $extraIds)->delete();
        }
    }

    private function removeNewSeperateClientsToBooking(Booking $booking, $snapshot)
    {
        $newSeperateClientsEmails = collect($snapshot['newSeperateClients'])->pluck('email')->filter()->toArray();
        BookingClient::whereHas('booking', function ($query) use ($booking) {
            $query->where('id', $booking->id);
        })->whereIn('client_id', Client::whereIn('email', $newSeperateClientsEmails)->pluck('id')->toArray())->delete();
    }
}
