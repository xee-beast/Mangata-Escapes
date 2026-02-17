<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewBooking extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $booking;

    /**
     * Create a new message instance.
     *
     * @param \App\Models\Booking $booking
     * @return void
     */
    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        if ($this->booking->group) {
            $subject_part = $this->booking->group->is_fit ? 'Quotation Form' : 'Booking Form';
            $subject = $this->booking->group->travel_agent->name . ' - ' . $this->booking->group->name . ' - ' . $subject_part;
        } else {
            $subject = 'Individual Booking - ' . $this->booking->full_name . ' - Quotation Form';
        }

        return $this->from(config('emails.no_reply'), 'Barefoot Bridal')
                    ->subject($subject)
                    ->view('web.mail.newBooking');
    }
}
