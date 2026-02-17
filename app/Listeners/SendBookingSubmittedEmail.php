<?php

namespace App\Listeners;

use App\Events\BookingSubmitted;
use App\Mail\NewBooking as BookingEmail;
use Illuminate\Support\Facades\Mail;

class SendBookingSubmittedEmail
{
    /**
     * Handle the event.
     *
     * @param  \App\Events\BookingSubmitted  $event
     * @return void
     */
    public function handle(BookingSubmitted $event)
    {
        Mail::to(config('emails.bookings'))->send(new BookingEmail($event->booking));
    }
}
