<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Group;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateMinimumDeposit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update-minimum-deposit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update minimum deposit for groups based on due dates and balance due date.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $today = Carbon::today('America/New_York');

        $groups = Group::where(function ($query) use ($today) {
                $query->where('balance_due_date', $today)
                    ->orWhereHas('due_dates', function ($q) use ($today) {
                        $q->where('date', $today);
                    });
            })
            ->with(['due_dates' => function ($q) use ($today) {
                $q->where('date', $today);
            }])
            ->get();

        foreach ($groups as $group) {
            if ($today->isSameDay($group->balance_due_date)) {
                $group->deposit = 100;
                $group->deposit_type = 'percentage';
                $group->save();

                continue;
            }

            $dueDate = $group->due_dates?->first();

            if ($dueDate) {
                if ($dueDate->type === 'percentage') {
                    $group->deposit_type = 'percentage';
                } elseif ($dueDate->type === 'price') {
                    $group->deposit_type = 'fixed';
                } elseif ($dueDate->type === 'nights') {
                    $group->deposit_type = 'nights';
                } else {
                    continue;
                }

                $group->deposit = $dueDate->amount;
                $group->save();
            }
        }

        $individual_bookings = Booking::whereNull('group_id')
            ->where(function ($query) use ($today) {
                $query->where('balance_due_date', $today)
                    ->orWhereHas('bookingDueDates', function ($q) use ($today) {
                        $q->where('date', $today);
                    });
            })
            ->with(['bookingDueDates' => function ($q) use ($today) {
                $q->where('date', $today);
            }])
            ->get();

        foreach ($individual_bookings as $booking) {
            if ($today->isSameDay($booking->balance_due_date)) {
                $booking->deposit = 100;
                $booking->deposit_type = 'percentage';
                $booking->save();

                continue;
            }

            $dueDate = $booking->bookingDueDates?->first();

            if ($dueDate) {
                if ($dueDate->type === 'percentage') {
                    $booking->deposit_type = 'percentage';
                } elseif ($dueDate->type === 'price') {
                    $booking->deposit_type = 'fixed';
                } elseif ($dueDate->type === 'nights') {
                    $booking->deposit_type = 'nights';
                } else {
                    continue;
                }

                $booking->deposit = $dueDate->amount;
                $booking->save();
            }
        }
    }
}
