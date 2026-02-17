<?php

namespace App\Notifications;

use App\Models\BookingClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FitQuoteAccepted extends Notification implements ShouldQueue
{
    use Queueable;

    protected $bookingClient;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(BookingClient $bookingClient)
    {
        $this->bookingClient = $bookingClient;
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
        if ($this->bookingClient->booking->group) {
            $subject = "{$this->bookingClient->booking->group->bride_last_name} & {$this->bookingClient->booking->group->groom_last_name}";
        } else {
            $subject = $this->bookingClient->booking->full_name;
        }

        return (new MailMessage)
            ->subject("{$subject} {$this->bookingClient->reservation_code} - You're In")
            ->greeting('VACAY VIBES ACTIVATED')
            ->line('You accepted your quote! 🎉 Our team is working on confirming your reservation with the hotel. Sit back, relax, and get ready for paradise.')
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
