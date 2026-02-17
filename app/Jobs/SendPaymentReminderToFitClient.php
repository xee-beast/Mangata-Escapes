<?php

namespace App\Jobs;

use App\Models\BookingClient;
use App\Notifications\FitClientPaymentReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendPaymentReminderToFitClient implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $bookingClient;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(BookingClient $bookingClient)
    {
        $this->bookingClient = $bookingClient;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->bookingClient->payments()->exists()) {
            return;
        }

        $this->bookingClient->client->notify(new FitClientPaymentReminder($this->bookingClient->booking));
    }
}
