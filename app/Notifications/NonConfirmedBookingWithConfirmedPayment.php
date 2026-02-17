<?php

namespace App\Notifications;

use App\Models\BookingClient;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NonConfirmedBookingWithConfirmedPayment extends Notification
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
            $cc = $group->travel_agent->email;
        } else {
            $subject = $booking->full_name;
            $cc = $booking->travel_agent ? $booking->travel_agent->email : config('emails.bookings');
        }

        return (new MailMessage)
            ->subject("{$subject} {$this->bookingClient->reservation_code} - Pending Reservation")
            ->greeting('Hi ' . $this->bookingClient->first_name . ',')
            ->line('Thank you for your continued patience while we wait on your room confirmation!')
            ->line('The hotels are busier than normal and it is taking longer than the usual 5-7 business days to receive confirmations on some reservations. Rest assured, there is nothing wrong with your booking; it is being worked on and an invoice will be sent to you as soon as it is confirmed.')
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
