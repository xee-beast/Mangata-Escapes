<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CardDeclined extends Notification implements ShouldQueue
{
    use Queueable;

    public $payment;

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
        $booking = $this->payment->booking_client->booking;
        $group = $booking->group;

        if ($group) {
            $subject = "{$group->bride_last_name} & {$group->groom_last_name}";
            $action = 'Go To ' . $group->name . '\'s Site';
            $route = route('couples', ['group' => $group->slug]);
            $cc = [config('emails.groups'), $group->travel_agent->email];
        } else {
            $subject = $booking->full_name;
            $action = 'Go To Our Booking Site';
            $route = route('individual-bookings.page');
            $cc = [config('emails.groups'), $booking->travel_agent ? $booking->travel_agent->email : config('emails.bookings')];
        }

        return (new MailMessage)
            ->subject("{$subject} {$this->payment->booking_client->reservation_code} - We’ve hit some turbulence")
            ->greeting('OH NO!')
            ->line('Your payment was declined.')
            ->line('Just call your bank to approve this transaction and tell us when we can run your card again.')
            ->line('Need to use a different card? Submit a payment here:')
            ->action($action, $route)
            ->cc($cc);
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
