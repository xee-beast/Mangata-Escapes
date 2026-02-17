<?php

namespace App\Notifications;

use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GroupEmail extends Notification implements ShouldQueue
{
    use Queueable;

    protected $client;
    protected $data;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Client $client, array $data)
    {
        $this->client = $client;
        $this->data = $data;
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
        $emailMessage = (new MailMessage)
            ->subject($this->data['subject'])
            ->greeting('Hi ' . $this->client->first_name . ',')
            ->line($this->data['message']);

        return $emailMessage;
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
