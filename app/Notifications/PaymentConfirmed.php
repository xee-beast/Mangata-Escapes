<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use PDF;

class PaymentConfirmed extends Notification implements ShouldQueue
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
        $booking = $this->payment->booking_client->booking;
        $group = $booking->group;

        if ($booking->group) {
            $subject = "{$group->bride_last_name} & {$group->groom_last_name}";
            $name = $group->name;
            $cc = $group->travel_agent->email;
        } else {
            $name = $subject = $booking->full_name;
            $cc = $booking->travel_agent ? $booking->travel_agent->email : config('emails.bookings');
        }

        return (new MailMessage)
            ->subject("{$subject} {$this->payment->booking_client->reservation_code} - One Step Closer")
            ->greeting('YAY!')
            ->line('Look at all the progress you’ve made.')
            ->line('Your updated invoice is attached.')
            ->attachData(
                PDF::loadView('pdf.invoice', ['invoice' => $booking->invoice])->output(),
                'R' . $booking->order . ' BB Invoice - ' . $name . '.pdf',
                ['mime' => 'application/pdf']
            )
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
