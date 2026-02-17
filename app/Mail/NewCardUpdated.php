<?php

namespace App\Mail;

use App\Models\BookingClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewCardUpdated extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $clientBooking;
    public $signedInsurance;

    /**
     * Create a new message instance.
     *
     * @param \App\Models\BookingClient $clientBooking
     * @param boolean $signedInsurance
     * @return void
     */
    public function __construct(BookingClient $clientBooking, $signedInsurance)
    {
        $this->clientBooking = $clientBooking;
        $this->signedInsurance = $signedInsurance;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $booking = $this->clientBooking->booking;
        $group = $booking->group;

        if ($group) {
            $subject = $group->travel_agent->name . ' - ' . $group->name . ' - Update Card Form';
        } else {
            $subject = ($booking->travel_agent ? $booking->travel_agent->name . ' - ' : '') . $booking->full_name . ' - Update Card Form';
        }

        return $this->from(config('emails.no_reply'), 'Barefoot Bridal')
            ->subject($subject)
            ->view('web.mail.newCardUpdate');
    }
}
