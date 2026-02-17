<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserCreated extends Notification
{
    use Queueable;

    /**
     * The user.
     *
     * @var string
     */
    public $user;

    /**
     * The password.
     *
     * @var string
     */
    public $password;

    /**
     * Create a new notification instance.
     *
     * @param App\User $password
     * @param String $password
     * @return void
     */
    public function __construct($user, $password)
    {
        $this->user = $user;
        $this->password = $password;
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
        return (new MailMessage)
            ->subject('A new user has been created!')
            ->greeting('A new user has been created!')
            ->line(sprintf("A user was created with the next information:\n"))
            ->line('First Name: <b>'.$this->user->first_name . '</b>')
            ->line('Last Name: <b>'.$this->user->last_name . '</b>')
            ->line('Username: <b>'.$this->user->username . '</b>')
            ->line('Password: <b>'.$this->password . '</b>')
            ->line('Please to notify to the respective user.')
            ->line('Thanks.');
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