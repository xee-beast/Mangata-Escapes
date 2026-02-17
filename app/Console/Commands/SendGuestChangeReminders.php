<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\GuestChange;
use App\Notifications\GuestChangeReminder as GuestChangeReminderNotification;
use Carbon\Carbon;

class SendGuestChangeReminders extends Command
{
    protected $signature = 'guest-changes:send-reminders';
    protected $description = 'Send reminder emails for pending guest changes after 5 and 8 business days';

    public function handle()
    {
        $now = Carbon::today('America/New_York');

        $fiveBusinessDaysAgo = $this->getBusinessDaysAgo($now, 5);
        $eightBusinessDaysAgo = $this->getBusinessDaysAgo($now, 8);

        $firstReminderChanges = GuestChange::whereDate('created_at', $fiveBusinessDaysAgo->format('Y-m-d'))
            ->whereNull('admin_confirmed_at')
            ->whereNull('admin_cancelled_at')
            ->whereNotNull('confirmed_at')
            ->whereNull('deleted_at')
            ->with(['bookingClient.client'])
            ->whereHas('bookingClient.booking.activeGroup', function ($query) {
                $query->where('disable_notifications', false);
            })
            ->get();

        foreach ($firstReminderChanges as $guestChange) {
            if ($guestChange->bookingClient && $guestChange->bookingClient->client) {
                try {
                    $guestChange->bookingClient->client->notify(new GuestChangeReminderNotification($guestChange->bookingClient, false));
                } catch (\Exception $e) {
                    $this->error('Failed to send email to: ' . $guestChange->bookingClient->client->email . ' - Error: ' . $e->getMessage());
                }
            }
        }

        $finalReminderChanges = GuestChange::whereDate('created_at', $eightBusinessDaysAgo->format('Y-m-d'))
            ->whereNull('admin_confirmed_at')
            ->whereNull('admin_cancelled_at')
            ->whereNotNull('confirmed_at')
            ->whereNull('deleted_at')
            ->with(['bookingClient.client'])
            ->whereHas('bookingClient.booking.activeGroup', function ($query) {
                $query->where('disable_notifications', false);
            })
            ->get();

        foreach ($finalReminderChanges as $guestChange) {
            if ($guestChange->bookingClient && $guestChange->bookingClient->client) {
                try {
                    $guestChange->bookingClient->client->notify(new GuestChangeReminderNotification($guestChange->bookingClient, true));
                } catch (\Exception $e) {
                    $this->error('Failed to send final reminder email to: ' . $guestChange->bookingClient->client->email . ' - Error: ' . $e->getMessage());
                }
            }
        }

        return 0;
    }

    private function getBusinessDaysAgo(Carbon $date, int $businessDays): Carbon
    {
        $result = $date->copy();
        $count = 0;

        while ($count < $businessDays) {
            $result->subDay();

            if ($result->dayOfWeek !== 0 && $result->dayOfWeek !== 6) {
                $count++;
            }
        }

        return $result;
    }
}
