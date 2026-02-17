<?php

namespace App\Notifications;

use App\Models\BookingClient;
use App\Models\GroupAirport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GuestChangeSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    protected $bookingClient;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(BookingClient $bookingClient)
    {
        $this->bookingClient = $bookingClient;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $group = $this->bookingClient->booking->group;
        $bookingUrl = config('app.dashboard_url') . '/groups/' . $group->id . '/bookings/' . $this->bookingClient->booking->id;

        $mail = (new MailMessage)
            ->subject("{$group->bride_last_name} & {$group->groom_last_name} {$this->bookingClient->reservation_code} - Guest Change Request Submitted")
            ->greeting(strtoupper($this->bookingClient->first_name . ' ' . $this->bookingClient->last_name) . ' HAS REQUESTED A RESERVATION CHANGE')
            ->line($this->bookingClient->first_name . ' ' . $this->bookingClient->last_name . ' has submitted a request to update their reservation.');

        $guestChange = $this->bookingClient->booking->guestChanges()
            ->where('booking_client_id', $this->bookingClient->id)
            ->latest()
            ->first();

        $paymentDetails = json_decode($guestChange->payment_details, true);

        if (isset($paymentDetails['confirmationMessages']) && !empty($paymentDetails['confirmationMessages'])) {
            $mail->line('NOTES');
            foreach ($paymentDetails['confirmationMessages'] as $message) {
                $mail->line('• ' . $message);
            }
        }

        if (isset($paymentDetails['shouldCCGroupsEmail']) && $paymentDetails['shouldCCGroupsEmail']) {
            $mail->cc(config('emails.groups'));
        }

        $changes = $this->getChanges($guestChange);

        foreach ($changes as $change) {
            $mail->line($change);
        }

        $mail->action('View Booking in Dashboard', $bookingUrl);

        return $mail;
    }

    private function getChanges($guestChange)
    {
        $booking = $this->bookingClient->booking;
        $booking->load(['clients.guests', 'roomBlocks.room.hotel']);

        $beforeData = [
            'special_requests' => $booking->special_requests,
            'guests' => $booking->clients->flatMap->guests
                ->map(fn($guest) => [
                    'id' => $guest->id,
                    'first_name' => $guest->first_name,
                    'last_name' => $guest->last_name,
                    'gender' => $guest->gender,
                    'birth_date' => $guest->birth_date ? date('Y-m-d', strtotime($guest->birth_date)) : null,
                    'check_in' => $guest->check_in ? date('Y-m-d', strtotime($guest->check_in)) : null,
                    'check_out' => $guest->check_out ? date('Y-m-d', strtotime($guest->check_out)) : null,
                    'insurance' => $guest->insurance,
                    'transportation' => $guest->transportation === null ? null : ($guest->transportation ? 'Yes' : 'No'),
                    'transportation_type' => $guest->transportation_type,
                    'custom_group_airport' => $guest->custom_group_airport,
                    'booking_client_id' => $guest->booking_client_id,
                ])->values(),
            'roomBlocks' => $booking->roomBlocks->map(fn($block) => [
                'id' => $block->id,
                'room_block_id' => $block->room_block_id,
                'name' => "{$block->room->name} at {$block->room->hotel->name}",
                'room_name' => $block->room->name,
                'bed' => $block->pivot->bed,
                'check_in' => $block->pivot->check_in ? date('Y-m-d', strtotime($block->pivot->check_in)) : null,
                'check_out' => $block->pivot->check_out ? date('Y-m-d', strtotime($block->pivot->check_out)) : null,
            ])
        ];

        $afterData = collect($guestChange->snapshot);
        $afterGuests = collect($afterData['clients'] ?? [])
            ->flatMap(function($client) { return collect($client['guests'] ?? [])->filter(fn($guest) => empty($guest['deleted_at']))->values();})
            ->filter(fn($guest) => $guest['booking_client_id'] === $this->bookingClient->id)
            ->map(function($guest) {
                    return [
                    'id' => $guest['id'] ?? null,
                    'first_name' => $guest['first_name'] ?? '',
                    'last_name' => $guest['last_name'] ?? '',
                    'gender' => $guest['gender'] ?? '',
                    'birth_date' => isset($guest['birth_date']) ? date('Y-m-d', strtotime($guest['birth_date'])) : null,
                    'check_in' => isset($guest['check_in']) ? date('Y-m-d', strtotime($guest['check_in'])) : null,
                    'check_out' => isset($guest['check_out']) ? date('Y-m-d', strtotime($guest['check_out'])) : null,
                    'insurance' => $guest['insurance'],
                    'transportation' => array_key_exists('transportation', $guest) ? ($guest['transportation'] ? 'Yes' : 'No') : null,
                    'transportation_type' => $guest['transportation_type'] ?? null,
                    'custom_group_airport' => $guest['custom_group_airport'] ?? null,
                    'booking_client_id' => $guest['booking_client_id'],
                ];
            });

        $changes = collect();
        $accommodationChanges = collect();
        $clientChanges = collect();
        $guestChanges = collect();

        $beforeSpecialRequests = $beforeData['special_requests'] ?? '';
        $afterSpecialRequests = $afterData['special_requests'] ?? '';
        if ($beforeSpecialRequests != $afterSpecialRequests) {
            $accommodationChanges->push(sprintf(
                "• Special Requests from '%s' to '%s'",
                $beforeSpecialRequests ?: 'None',
                $afterSpecialRequests ?: 'None'
            ));
        }

        $beforeRooms = collect($beforeData['roomBlocks']);
        $afterRooms = collect($afterData['roomBlocks'] ?? [])
            ->map(fn($block) => [
                'name' => "{$block['room']['name']} at {$block['room']['hotel']['name']}",
                'room_name' => $block['room']['name'],
                'bed' => $block['bed'] ?? $block['pivot']['bed'],
                'check_in' => isset($block['check_in']) ? date('Y-m-d', strtotime($block['check_in'])) : (isset($block['pivot']['check_in']) ? date('Y-m-d', strtotime($block['pivot']['check_in'])) : null),
                'check_out' => isset($block['check_out']) ? date('Y-m-d', strtotime($block['check_out'])) : (isset($block['pivot']['check_out']) ? date('Y-m-d', strtotime($block['pivot']['check_out'])) : null),
            ]);

        $beforeRooms->pluck('name')->diff($afterRooms->pluck('name'))->each(function($name) use($beforeRooms, $accommodationChanges) {
            $room = $beforeRooms->firstWhere('name', $name);
            $accommodationChanges->push("• Removed Room: {$name} - {$room['bed']} ({$room['check_in']} to {$room['check_out']})");
        });

        $afterRooms->pluck('name')->diff($beforeRooms->pluck('name'))->each(function($name) use($afterRooms, $accommodationChanges) {
            $room = $afterRooms->firstWhere('name', $name);
            $accommodationChanges->push("• Added Room: {$name} - {$room['bed']} ({$room['check_in']} to {$room['check_out']})");
        });

        $beforeRooms->pluck('name')->intersect($afterRooms->pluck('name'))->each(function($name) use($beforeRooms, $afterRooms, $accommodationChanges) {
            $before = $beforeRooms->firstWhere('name', $name);
            $after = $afterRooms->firstWhere('name', $name);

            if ($before['bed'] !== $after['bed'] || $before['check_in'] !== $after['check_in'] || $before['check_out'] !== $after['check_out']) {
                $accommodationChanges->push("• {$before['room_name']}");
                if ($before['bed'] !== $after['bed']) {
                    $accommodationChanges->push("&nbsp;&nbsp;• Bed Type from {$before['bed']} to {$after['bed']}");
                }

                if ($before['check_in'] !== $after['check_in'] || $before['check_out'] !== $after['check_out']) {
                    $accommodationChanges->push("&nbsp;&nbsp;• Check-in/Check-out from {$before['check_in']} - {$before['check_out']} to {$after['check_in']} - {$after['check_out']}");
                }
            }
        });

        $beforeGuests = collect($beforeData['guests']);
        $afterGuests = collect($afterGuests);

        $allAfterGuests = collect($afterData['clients'] ?? [])
            ->flatMap(function($client) {
                $guests = $client['guests'] ?? [];
                if (!is_array($guests)) {
                    $guests = (array)$guests;
                }
                return collect($guests)->values();
            });

        $beforeGuests->each(function($beforeGuest) use($afterGuests, $allAfterGuests, $guestChanges, $afterData) {
            $afterGuest = $allAfterGuests->filter(fn($guest) => !$guest['deleted_at'])->firstWhere('id', $beforeGuest['id']);

            if (!$afterGuest) {
                $guestChanges->push("Removed Guest: {$beforeGuest['first_name']} {$beforeGuest['last_name']}");
                return;
            }

            $guestName = "{$beforeGuest['first_name']} {$beforeGuest['last_name']}";
            $guestLines = collect();

            if ($beforeGuest['booking_client_id'] !== $afterGuest['booking_client_id']) {
                $beforeClientName = $this->getClientName($beforeGuest['booking_client_id'], $afterData);
                $afterClientName = $this->getClientName($afterGuest['booking_client_id'], $afterData);
                $guestLines->push("&nbsp;&nbsp;• Client Changed from '{$beforeClientName}' to '{$afterClientName}'");
            }

            $normalize = function($value, $field) {
                if ($field === 'Insurance') {
                    if ($value === null) return 'Pending';
                    return $value ? 'Yes' : 'No';
                }
                if ($field === 'Transportation') {
                    if ($value == null) return null;
                    return $value ? 'Yes' : 'No';
                }
                return $value;
            };

            $fields = [
                'First Name' => ['before' => $beforeGuest['first_name'], 'after' => $afterGuest['first_name']],
                'Last Name' => ['before' => $beforeGuest['last_name'], 'after' => $afterGuest['last_name']],
                'Gender' => ['before' => $beforeGuest['gender'], 'after' => $afterGuest['gender']],
                'Birth Date' => ['before' => $beforeGuest['birth_date'], 'after' => $afterGuest['birth_date']],
                'Check In' => ['before' => $beforeGuest['check_in'], 'after' => $afterGuest['check_in']],
                'Check Out' => ['before' => $beforeGuest['check_out'], 'after' => $afterGuest['check_out']],
                'Insurance' => ['before' => $beforeGuest['insurance'], 'after' => $afterGuest['insurance']],
                'Transportation' => ['before' => $beforeGuest['transportation'], 'after' => $afterGuest['transportation']],
                'Transportation Type' => [
                    'before' => $this->formatTransportationType($beforeGuest['transportation_type']),
                    'after' => $this->formatTransportationType($afterGuest['transportation_type'])
                ],
                'Custom Airport' => [
                    'before' => $this->formatCustomAirport($beforeGuest['custom_group_airport']),
                    'after' => $this->formatCustomAirport($afterGuest['custom_group_airport'])
                ],
            ];

            foreach ($fields as $field => $data) {
                $before = $normalize($data['before'], $field);
                $after = $normalize($data['after'], $field);

                if ($field === 'Transportation' && ($before === null || $after === null)) {
                    continue;
                }

                if ($before !== $after && $before !== null) {
                    $guestLines->push("&nbsp;&nbsp;• {$field} from '{$before}' to '{$after}'");
                }
            }

            if ($guestLines->isNotEmpty()) {
                $guestChanges->push("• {$guestName}:");
                $guestChanges->push(...$guestLines);
            }
        });

        $allAfterGuests->each(function($guest) use($guestChanges) {
            if (!isset($guest['id'])) {
                $guestChanges->push("Added Guest: {$guest['first_name']} {$guest['last_name']}");
            }
        });

        collect($afterData['newSeperateClients'] ?? [])->each(function($client) use($clientChanges) {
            $clientName = ($client['firstName'] ?? '') . ' ' . ($client['lastName'] ?? '');
            $clientChanges->push("Added Client: {$clientName}");
        });

        if ($accommodationChanges->isNotEmpty()) {
            $changes->push('ACCOMMODATION CHANGES');
            $changes->push(...$accommodationChanges);
        }

        if ($clientChanges->isNotEmpty()) {
            $changes->push('CLIENT CHANGES');
            $changes->push(...$clientChanges);
        }

        if ($guestChanges->isNotEmpty()) {
            $changes->push('GUEST CHANGES');
            $changes->push(...$guestChanges);
        }

        return $changes->all();
    }

    private function formatTransportationType($transportationType)
    {
        return match ($transportationType) {
            1 => 'Round Trip',
            2 => 'One Way Airport to Hotel',
            3 => 'One Way Hotel to Airport',
            default => 'None'
        };
    }

    private function formatCustomAirport($customAirport)
    {
        $groupAirport = GroupAirport::find($customAirport);
        return $groupAirport ? $groupAirport->airport?->airport_code : 'None';
    }

    private function getClientName($bookingClientId, $snapshot = null)
    {
        $bookingClient = $this->bookingClient->booking->clients->firstWhere('id', $bookingClientId);

        if ($bookingClient) {
            return "{$bookingClient->first_name} {$bookingClient->last_name}";
        }

        if (is_string($bookingClientId) && filter_var($bookingClientId, FILTER_VALIDATE_EMAIL) && $snapshot) {
            $newClient = collect($snapshot['clients'] ?? [])->first(function ($client) use ($bookingClientId) {
                return !isset($client['id']) && isset($client['guests']) &&
                       collect($client['guests'])->contains('booking_client_id', $bookingClientId);
            });

            if ($newClient) {
                return "{$newClient['first_name']} {$newClient['last_name']}";
            }
        }
    }
}
