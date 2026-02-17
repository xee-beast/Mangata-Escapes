<?php

namespace App\Mail;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewPayment extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $payment;
    public $signedInsurance;
    public $type;

    /**
     * Create a new message instance.
     *
     * @param \App\Models\Payment $payment
     * @param boolean $signedInsurance
     * @return void
     */
    public function __construct(Payment $payment, $signedInsurance, $type)
    {
        $this->payment = $payment;
        $this->signedInsurance = $signedInsurance;
        $this->type = $type;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $booking = $this->payment->booking_client->booking;
        $group = $booking->group;

        if ($group) {
            $subject = $group->travel_agent->name . ' - ' . $group->name . ' - Payment Form';
        } else {
            $subject = ($booking->travel_agent ? $booking->travel_agent->name . ' - ' : '') . $booking->full_name . ' - Payment Form';
        }
   
        return $this->from(config('emails.no_reply'), 'Barefoot Bridal')
            ->subject($subject)
            ->view('web.mail.newPayment');
}
}
