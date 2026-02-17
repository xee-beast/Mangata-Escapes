<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FitClientPaymentReminder extends Notification implements ShouldQueue
{
    use Queueable;

    protected $booking;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Booking $booking)
    {
        $this->booking = $booking->loadMissing(['group', 'clients']);
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
        if ($this->booking->group) {
            $subject = "{$this->booking->group->bride_last_name} & {$this->booking->group->groom_last_name}";
            $action_text = 'Go To ' . $this->booking->group->name . '\'s Site';
            $route = route('couples', ['group' => $this->booking->group->slug]);
        } else {
            $subject = $this->booking->full_name;
            $action_text = 'Go To Our Booking Site';
            $route = route('individual-bookings.page');
        }

        return (new MailMessage)
            ->subject("{$subject} {$this->booking->clients->firstWhere('client_id', $notifiable->id)->reservation_code} - We just need one more thing")
            ->greeting('ALMOST THERE!')
            ->line('OOPS! You accepted your quote, but we cannot confirm it without a deposit.')
            ->line('Don\'t worry, it\'s easy. Use reservation code <b>' . $this->booking->clients->firstWhere('client_id', $notifiable->id)->reservation_code . '</b> on the couple\'s website with the Make a Payment button to finalize your booking.')
            ->action($action_text, $route);
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
