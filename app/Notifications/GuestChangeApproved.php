<?php

namespace App\Notifications;

use App\Models\BookingClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GuestChangeApproved extends Notification implements ShouldQueue
{
    use Queueable;

    protected $bookingClient;

    public function __construct(BookingClient $bookingClient)
    {
        $this->bookingClient = $bookingClient;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $group = $this->bookingClient->booking->group;

        return (new MailMessage)
            ->subject("{$group->bride_last_name} & {$group->groom_last_name} {$this->bookingClient->reservation_code} - Change in progress")
            ->greeting('SIT TIGHT')
            ->line('We are working on your change request!')
            ->line('We\'ve sent it to the hotel for approval, but they can take up to a week to confirm.')
            ->line('Don\'t worry, we will keep you updated as soon as we hear from the hotel.')
            ->line('Questions while you wait?')
            ->line('Just hit reply! Your Guest Experience Concierges are on stand-by.');
    }

    public function toArray($notifiable)
    {
        return [
            'booking_id' => $this->bookingClient->booking->id,
            'group_name' => $this->bookingClient->booking->group->bride_last_name . ' & ' . $this->bookingClient->booking->group->groom_last_name,
        ];
    }
}
