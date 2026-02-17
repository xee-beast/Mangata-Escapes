<?php

namespace App\Notifications;

use App\Models\Group;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendCouplesSitePasswordNotification extends Notification implements ShouldQueue
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
            ->subject("{$this->group->bride_last_name} & {$this->group->groom_last_name} - Your Site is Now Password Protected!")
            ->greeting('HI THERE…')
            ->line('This is your site\'s password that we generated for you. This will help in stopping any strays from accessing your personal site.')
            ->line('Only share this password with people that you want to access your site.')
            ->line('Password: <b>' . $this->group->couples_site_password . '</b>')
            ->action('To access your site, go to ' . $this->group->name . '\'s Site', route('couples', ['group' => $this->group->slug]));
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
