<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use PDF;

class InvoiceMail extends Notification implements ShouldQueue
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
        $this->booking = $booking;
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
            $name = $this->booking->group->name;
        } else {
            $subject = $this->booking->full_name;
            $name = $this->booking->full_name;
        }

        return (new MailMessage)
            ->subject("{$subject} - Your Invoice")
            ->greeting('IS ATTACHED')
            ->line('Please review and let us know if you need any changes.')
            ->attachData(
                PDF::loadView('pdf.invoice', ['invoice' => $this->booking->invoice])->output(),
                'R' . $this->booking->order . ' BB Invoice - ' . $name . '.pdf',
                ['mime' => 'application/pdf']
            )
            ->cc([
                config('emails.groups'),
            ]);
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
