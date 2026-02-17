<?php

namespace App\Console\Commands;

use App\Models\FitQuote;
use App\Notifications\FitQuoteReminder;
use Illuminate\Console\Command;

class SendFitQuoteReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fit-quote-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command sends reminders to clients about their fit quotes.';

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
        $fit_quotes = FitQuote::where(function ($query) {
                $query->whereHas('bookingClient.booking.activeGroup', function ($groupQuery) {
                    $groupQuery->where('disable_notifications', false);
                })->orWhereDoesntHave('bookingClient.booking.group');
            })
            ->where('is_cancelled', false)
            ->whereNull('accepted_at')
            ->where(function ($query) {
                $query->whereDate('expiry_date_time', now()->startOfDay()->copy()->addDays(7))
                    ->orWhereDate('expiry_date_time', now()->startOfDay()->copy()->addDays(3))
                    ->orWhereDate('expiry_date_time', now()->startOfDay()->copy()->addDay());
            })
            ->get();

        foreach ($fit_quotes as $fit_quote) {
            $fit_quote->bookingClient->client->notify(new FitQuoteReminder($fit_quote));
        }
    }
}
