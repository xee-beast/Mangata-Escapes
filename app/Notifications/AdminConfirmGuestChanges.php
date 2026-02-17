<?php

namespace App\Notifications;

use App\Models\BookingClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use PDF;

class AdminConfirmGuestChanges extends Notification implements ShouldQueue
{
    use Queueable;

    protected $bookingClient;

    public function __construct(BookingClient $bookingClient)
    {
        $this->bookingClient = $bookingClient;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $group = $this->bookingClient->booking->group;
        $booking = $this->bookingClient->booking;

        return (new MailMessage)
            ->subject("{$group->bride_last_name} & {$group->groom_last_name} {$this->bookingClient->reservation_code} – Change request confirmed")
            ->greeting('YOU\'RE ALL SET')
            ->line('Your reservation has been updated and an updated invoice is attached!')
            ->attachData(
                PDF::loadView('pdf.invoice', ['invoice' => $booking->invoice])->output(),
                'R' . $booking->order . ' BB Invoice - ' . $booking->group->name . '.pdf',
                ['mime' => 'application/pdf']
            )
            ->line('Now you can get back to planning the fun parts of your trip.')
            ->cc(config('emails.groups'));
    }

    public function toArray($notifiable)
    {
        return [
            'booking_id' => $this->bookingClient->booking->id,
            'group_name' => $this->bookingClient->booking->group->bride_last_name . ' & ' . $this->bookingClient->booking->group->groom_last_name,
        ];
    }
}
