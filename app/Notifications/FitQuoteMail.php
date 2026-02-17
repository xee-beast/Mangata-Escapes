<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\FitQuote;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use PDF;

class FitQuoteMail extends Notification implements ShouldQueue
{
    use Queueable;

    protected $booking;
    protected $fitQuote;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Booking $booking, FitQuote $fitQuote)
    {
        $this->booking = $booking;
        $this->fitQuote = $fitQuote;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        if ($this->booking->group) {
            $name = $this->booking->group->name;
            $subject = "{$this->booking->group->bride_last_name} & {$this->booking->group->groom_last_name}";
            $site = $name . '\'s Site';
            $route = route('couples', ['group' => $this->booking->group->slug]);
        } else {
            $name = $subject = $this->booking->full_name;
            $site = 'Our Booking Site';
            $route = route('individual-bookings.page');
        }

        return (new MailMessage)
            ->subject("{$subject} {$this->fitQuote->bookingClient->reservation_code} - Your Quote is In")
            ->greeting('VACAY MODE ON STANDBY')
            ->line('Here’s your custom travel quote — just for you. Take a look, give it a thumbs up (aka click to accept), and we’ll take it from there. Keep in mind, prices and availability can change fast — so don’t wait too long! Got questions? We’ve got answers.')
            ->line('The quote will expire after ' . Carbon::parse($this->fitQuote->expiry_date_time)->setTimezone('America/New_York')->format('m-d-Y h:i A T') . '.')
            ->line('To accept the quote, go to ' . $site . ' and click on the "Accept Quote" button.')
            ->action('Go To ' . $site, $route)
            ->attachData(
                PDF::loadView('pdf.invoice', ['invoice' => $this->booking->invoice])->output(),
                'R' . $this->booking->order . ' BB Quotation Invoice - ' . $name . '.pdf',
                ['mime' => 'application/pdf']
            )
            ->cc([
               config('emails.groups')
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
