<?php

namespace App\Notifications;

use App\Models\DueDate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentDueDateReminder extends Notification implements ShouldQueue
{
    use Queueable;

    protected $params;
    protected $dueDate;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Object $params, $dueDate)
    {
        $this->params = $params;
        $this->dueDate = $dueDate;
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
        if ($this->dueDate->group_id) {
            $subject = "{$this->dueDate->group->bride_last_name} & {$this->dueDate->group->groom_last_name}";
        } else {
            $subject = $this->dueDate->booking->full_name;
        }

        return (new MailMessage)
            ->subject("{$subject} {$this->params->reservation_code} - We're here to remind you of your upcoming payment")
            ->greeting("PSST!")
            ->line('Don\'t forget:')
            ->line('On ' . $this->dueDate->date->format('F jS, Y') . ', we will automatically draft $ ' . number_format($this->params->amount, 2) . ' to your main card. Any cancellations must be made in writing 5 days prior to this date. ')
            ->line('That\'s all for now, but let us know if you have any questions.');
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