<?php

namespace App\Console\Commands;

use App\Models\FitQuote;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SoftDeleteBookingsWithExpiredQuote extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bookings-with-expired-quote:soft-delete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete bookings that recently had a quote expired.';

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
        $fit_quotes = FitQuote::whereBetween('expiry_date_time', [Carbon::now()->subMinutes(5), Carbon::now()])->whereNull('accepted_at')->where('is_cancelled', 0)->get();

        foreach ($fit_quotes as $fit_quote) {
            $fit_quote->bookingClient->booking->delete();
        }
    }
}
