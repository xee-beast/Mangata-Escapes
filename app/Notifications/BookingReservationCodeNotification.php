<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class BookingReservationCodeNotification extends BaseNotification
{
    protected $bookingClients;

    /**
     * Create a new notification instance.
     *
     * @param $bookingClients
     * @return void
     */
    public function __construct($bookingClients)
    {
        $this->bookingClients = $bookingClients;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array
     */
    protected function channels()
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
        $booking = $this->bookingClients->first()->booking;
        $group = $booking->group;

        if ($group) {
            $subject = "{$group->bride_last_name} & {$group->groom_last_name}";
            $action_text = 'Go To ' . $group->name . '\'s Site';
            $action_route = route('couples', ['group' => $group->slug]);
        } else {
            $subject = $booking->full_name;
            $action_text = 'Go To Our Booking Site';
            $action_route = route('individual-bookings.page');
        }

        return (new MailMessage)
            ->subject("{$subject} - Reservation Code Reminder")
            ->greeting('WE KNOW…')
            ->line('It’s not easy to remember a 6-digit computer generated code.')
            ->line('That’s why we’re happy to send it to you: <b>' . $this->bookingClients->pluck('reservation_code')->join('</b>, <b>', '</b> and <b>') . '</b>.')
            ->action($action_text, $action_route);
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
