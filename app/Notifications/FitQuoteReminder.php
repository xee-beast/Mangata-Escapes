<?php

namespace App\Notifications;

use App\Models\FitQuote;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use PDF;

class FitQuoteReminder extends Notification implements ShouldQueue
{
    use Queueable;

    protected $fitQuote;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(FitQuote $fitQuote)
    {
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
        $hasGroup = $this->fitQuote->bookingClient->booking->group;

        if ($hasGroup) {
            $subject = "{$this->fitQuote->bookingClient->booking->group->bride_last_name} & {$this->fitQuote->bookingClient->booking->group->groom_last_name} {$this->fitQuote->bookingClient->reservation_code} - Your Window’s Closing Soon";
            $name = $this->fitQuote->bookingClient->booking->group->name;
            $site = $name . '\'s Site';
            $route = route('couples', ['group' => $this->fitQuote->bookingClient->booking->group->slug]);
        } else {
            $subject = "{$this->fitQuote->bookingClient->booking->full_name} {$this->fitQuote->bookingClient->reservation_code} - Your Window’s Closing Soon";
            $name = $this->fitQuote->bookingClient->booking->full_name;
            $site = 'Our Booking Site';
            $route = route('individual-bookings.page');
        }

        return (new MailMessage)
            ->subject($subject)
            ->greeting('ALMOST WHEELS UP!')
            ->line('Your quote is still active, but not for much longer. Resorts only hold rates for a limited time and your quote expires at ' . Carbon::parse($this->fitQuote->expiry_date_time)->setTimezone('America/New_York')->format('m-d-Y h:i A T') . '. Still planning to book? Let’s lock it in before prices take off by accepting your quote.')
            ->line('To accept the quote, go to ' . $site . ' and click on the "Accept Quote" button.')
            ->action('Go To ' . $site, $route)
            ->attachData(
                PDF::loadView('pdf.invoice', ['invoice' => $this->fitQuote->bookingClient->booking->invoice])->output(),
                'R' . $this->fitQuote->bookingClient->booking->order . ' BB Quotation Invoice - ' . $name . '.pdf',
                ['mime' => 'application/pdf']
            )
            ->cc([
                config('emails.groups'),
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
