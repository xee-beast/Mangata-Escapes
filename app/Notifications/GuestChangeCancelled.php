<?php

namespace App\Notifications;

use App\Models\BookingClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GuestChangeCancelled extends Notification
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
        $group = $this->bookingClient->booking->group;

        return (new MailMessage)
            ->subject("{$group->bride_last_name} & {$group->groom_last_name} {$this->bookingClient->reservation_code} - Change Unavailable")
            ->greeting('DETOUR AHEAD')
            ->line('We reviewed your change request, but unfortunately, it\'s not possible to accommodate.')
            ->line('The good news? There may be other routes to get you where you want to go.')
            ->line('Just hit reply and we\'ll help you figure it out!')
            ->line('Your Guest Experience Concierges are on stand-by.');
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
            'booking_id' => $this->bookingClient->booking->id,
            'group_name' => $this->bookingClient->booking->group->bride_last_name . ' & ' . $this->bookingClient->booking->group->groom_last_name,
        ];
    }
}
