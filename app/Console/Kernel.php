<?php

namespace App\Console;

use App\Console\Commands\CreatePayments;
use App\Console\Commands\DeleteTravelDocuments;
use App\Console\Commands\SendFitQuoteReminders;
use App\Console\Commands\SoftDeleteBookingsWithExpiredQuote;
use App\Console\Commands\SendGuestChangeReminders;
use App\Console\Commands\UpdateMinimumDeposit;
use App\Tasks\FinalFlightManifestReminders;
use App\Tasks\LastFlightManifestReminders;
use App\Tasks\SendFlightManifestReminders;
use App\Tasks\BalanceDueDateReminders;
use App\Tasks\CancellationsLastCalls;
use App\Tasks\PaymentDueDateReminders;
use App\Tasks\FinalEmail;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Tasks\NonConfirmedBookingWithConfirmedPayment;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(new SendFlightManifestReminders)->dailyAt('10:00');
        $schedule->call(new FinalFlightManifestReminders)->dailyAt('10:00');
        $schedule->call(new LastFlightManifestReminders)->dailyAt('10:00');
        $schedule->call(new BalanceDueDateReminders)->dailyAt('12:00');
        $schedule->call(new CancellationsLastCalls)->dailyAt('14:00');
        $schedule->call(new PaymentDueDateReminders)->dailyAt('16:00');
        $schedule->call(new FinalEmail)->dailyAt('18:00');
        // $schedule->call(new NonConfirmedBookingWithConfirmedPayment)->dailyAt('20:00');
        $schedule->command(CreatePayments::class)->dailyAt('00:00')->timezone('America/New_York');
        $schedule->command(DeleteTravelDocuments::class)->dailyAt('00:00')->timezone('America/New_York');
        $schedule->command(SendFitQuoteReminders::class)->dailyAt('10:00');
        $schedule->command(SoftDeleteBookingsWithExpiredQuote::class)->everyMinute();
        $schedule->command(SendGuestChangeReminders::class)->dailyAt('00:00')->timezone('America/New_York');
        $schedule->command(UpdateMinimumDeposit::class)->dailyAt('00:00')->timezone('America/New_York');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
