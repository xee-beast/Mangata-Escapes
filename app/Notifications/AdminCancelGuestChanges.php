<?php

namespace App\Notifications;

use App\Models\BookingClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminCancelGuestChanges extends Notification implements ShouldQueue
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
            ->subject("{$group->bride_last_name} & {$group->groom_last_name} {$this->bookingClient->reservation_code} – Change Unavailable")
            ->greeting('OH NO!')
            ->line('We tried to make your change happen, but the hotel was unable to accommodate.')
            ->line('No changes have been made to your reservation.')
            ->line('If you\'d like to explore other options, just hit reply and your Guest Experience Concierges are on stand-by to help you find the next best fit!');
    }

    public function toArray($notifiable)
    {
        return [
            'booking_id' => $this->bookingClient->booking->id,
            'group_name' => $this->bookingClient->booking->group->bride_last_name . ' & ' . $this->bookingClient->booking->group->groom_last_name,
        ];
    }
}
