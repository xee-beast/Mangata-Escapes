<?php

namespace App\Notifications;

use App\Models\BookingClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FinalEmail extends Notification implements ShouldQueue
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
        $booking = $this->bookingClient->booking;
        $group = $booking->group;

        if ($group) {
            $subject = "{$group->bride_last_name} & {$group->groom_last_name}";
            $phoneNumber = $group->provider->phone_number;
            $email = $group->provider->email;
            $destination = $group->destination->country->name;
        } else {
            $subject = $booking->full_name;
            $phoneNumber = $booking->provider ? $booking->provider->phone_number : null;
            $email = $booking->provider ? $booking->provider->email : null;
            $destination = $booking->destination ? $booking->destination->country->name : null;
        }

        $mail = (new MailMessage)
            ->subject("{$subject} {$this->bookingClient->reservation_code} - It’s almost time to check in")
            ->greeting("AND GO BAREFOOT");
        
        if ($destination) {
            $mail->line("Welcome to <b>" . $destination . "</b>!");
        }

        if ($email && $phoneNumber) {
            $mail->line("We hope things go as smoothly as possible, but if there is any trouble in paradise regarding your room, please be sure to contact your in-travel customer service team at <b>" . $phoneNumber . "</b> or <b>" . $email . "</b>, and contact your transportation provider separately via WhatsApp with the number on your documents or email <b>".config('emails.groups')."</b>.");
        } else {
            $mail->line("We hope things go as smoothly as possible, but if there is any trouble please be sure to contact your in-travel customer service team and contact your transportation provider separately via WhatsApp with the number on your documents or email <b>".config('emails.groups')."</b>.");
        }

        return $mail;
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
