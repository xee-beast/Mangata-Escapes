<?php

namespace App\Notifications;

use App\Models\BookingClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CancellationsLastCalls extends Notification implements ShouldQueue
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
        $booking = $this->bookingClient->booking;
        $group = $booking->group;

        if ($group) {
            $subject = "{$group->bride_last_name} & {$group->groom_last_name}";
            $cancellation_date = $group->cancellation_date->format('F jS, Y');
        } else {
            $subject = $booking->full_name;
            $cancellation_date = $booking->cancellation_date ? $booking->cancellation_date->format('F jS, Y') : 'the cancellation date';
        }

        return (new MailMessage)
            ->subject("{$subject} {$this->bookingClient->reservation_code} - Just a quick reminder")
            ->greeting("LAST CALL")
            ->line('We would be sad to see you go, but if you need to cancel, now is the time to let us know.')
            ->line('All cancellations must be received in writing 5 days prior to ' . $cancellation_date . ', for refunds. Travel insurance is non-refundable.');            
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