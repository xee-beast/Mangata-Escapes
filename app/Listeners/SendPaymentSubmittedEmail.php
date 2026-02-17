<?php

namespace App\Listeners;

use App\Events\PaymentSubmitted;
use App\Mail\NewPayment as PaymentEmail;
use Illuminate\Support\Facades\Mail;

class SendPaymentSubmittedEmail
{
    /**
     * Handle the event.
     *
     * @param \App\Events\PaymentSubmitted  $event
     * @return void
     */
    public function handle(PaymentSubmitted $event)
    {
        Mail::to(config('emails.bookings'))->send(new PaymentEmail($event->payment, $event->signedInsurance, $event->type));
    }
}
