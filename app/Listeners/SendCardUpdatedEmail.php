<?php

namespace App\Listeners;

use App\Events\CardUpdated;
use App\Mail\NewCardUpdated as CardUpdatedEmail;
use Illuminate\Support\Facades\Mail;

class SendCardUpdatedEmail
{
    /**
     * Handle the event.
     *
     * @param \App\Events\CardUpdated
     * @return void
     */
    public function handle(CardUpdated $event)
    {
        Mail::to(config('emails.bookings'))->send(new CardUpdatedEmail($event->clientBooking, $event->signedInsurance));
    }
}
