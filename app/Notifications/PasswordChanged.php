<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordChanged extends Notification
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
            ->subject('A password has been changed!')
            ->greeting('A password has been changed!')
            ->line(sprintf("The password of the user <b>%s</b> has been changed", $this->user->username))
            ->line('The new password is: <b>'.$this->password . '</b>')
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