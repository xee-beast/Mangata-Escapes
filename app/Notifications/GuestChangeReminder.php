<?php

namespace App\Notifications;

use App\Models\BookingClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GuestChangeReminder extends Notification implements ShouldQueue
{
    use Queueable;

    protected $bookingClient;
    protected $isFinal;

    public function __construct(BookingClient $bookingClient, $isFinal = false)
    {
        $this->bookingClient = $bookingClient;
        $this->isFinal = $isFinal;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $group = $this->bookingClient->booking->group;

        if ($this->isFinal) {
            $subject = "{$group->bride_last_name} & {$group->groom_last_name} {$this->bookingClient->reservation_code} - We've Escalated On Your Behalf";
            $greeting = 'KICKING IT UP A NOTCH';

            $message = (new MailMessage)
                ->subject($subject)
                ->greeting($greeting)
                ->line('Your changes are taking longer than usual, but don\'t worry - we\'ve escalated it to management.')
                ->line('We\'re on it and we\'ll update you the moment we have news.')
                ->line('Your Guest Experience Concierges are on stand-by if you need anything in the meantime.')
                ->cc(config('emails.groups'));
        } else {
            $subject = "{$group->bride_last_name} & {$group->groom_last_name} {$this->bookingClient->reservation_code} - Changes Still in Progress";
            $greeting = 'WE HAVEN\'T FORGOTTEN';

            $message = (new MailMessage)
                ->subject($subject)
                ->greeting($greeting)
                ->line('Some changes take longer to confirm, but we promise you\'re still on our radar!')
                ->line('We\'ll keep you updated as soon as we hear back.')
                ->line('Questions in the meantime? Your Guest Experience Concierges are on stand-by.');
        }

        return $message;
    }

    public function toArray($notifiable)
    {
        return [
            'booking_id' => $this->bookingClient->booking->id,
            'group_name' => $this->bookingClient->booking->group->bride_last_name . ' & ' . $this->bookingClient->booking->group->groom_last_name,
            'is_final' => $this->isFinal,
        ];
    }
}
