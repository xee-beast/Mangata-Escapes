<?php

namespace App\Notifications;

use App\Models\BookingClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentInformationUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $client;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(BookingClient $client)
    {
        $this->client = $client;
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
        if ($this->client->booking->group) {
            $subject = "{$this->client->booking->group->bride_last_name} & {$this->client->booking->group->groom_last_name}";
        } else {
            $subject = $this->client->booking->full_name;
        }

        return (new MailMessage)
            ->subject("{$subject} {$this->client->reservation_code} - Nice Update!")
            ->greeting(('YAY!'))
            ->line('We have your new payment information and will use it for all future payments towards your reservation.');
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
