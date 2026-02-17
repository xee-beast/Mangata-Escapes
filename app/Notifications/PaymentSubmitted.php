<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    protected $payment;

    /**
     * Create a new notification instance.
     *
     * @param \App\Models\Payment $payment
     * @return void
     */
    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
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
        if ($this->payment->booking_client->booking->group) {
            $subject = "{$this->payment->booking_client->booking->group->bride_last_name} & {$this->payment->booking_client->booking->group->groom_last_name}";
        } else {
            $subject = "{$this->payment->booking_client->booking->full_name}";
        }

        return (new MailMessage)
            ->subject("{$subject} {$this->payment->booking_client->reservation_code} - Oh hey, you’ve submitted a payment")
            ->greeting('HANG TIGHT!')
            ->line('Let us work on this for you.')
            ->line('You will receive an updated invoice soon.');                 
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
