<?php

namespace App\Notifications;

use App\Models\Group;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GroupPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $group;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Group $group)
    {
        $this->group = $group;
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
            ->subject("{$this->group->bride_last_name} & {$this->group->groom_last_name} - Password Reminder")
            ->greeting('WE KNOW…')
            ->line('It’s not easy to remember a password that someone else generated for you.')
            ->line('That’s why we’re happy to send it to you: <b>' . $this->group->password . '</b>')
            ->action('Go To ' . $this->group->name . '\'s Site', route('couples', ['group' => $this->group->slug]));
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
