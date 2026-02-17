<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\FitQuote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FitQuoteCancelled extends Notification implements ShouldQueue
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
            $subject = "{$this->booking->group->bride_last_name} & {$this->booking->group->groom_last_name}";
        } else {
            $subject = $this->booking->full_name;
        }

        return (new MailMessage)
            ->subject("{$subject} {$this->fitQuote->bookingClient->reservation_code} - Your Final Boarding Call Was Missed")
            ->greeting('NO NEED TO WORRY!')
            ->line('Your quote has officially expired. Rates aren’t held forever and with popular resorts, they move fast. Want us to check the current price and availability? We’re happy to take another look—just say the word.')
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
