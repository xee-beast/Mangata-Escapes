<?php

namespace App\Notifications;

use App\Models\BookingClient;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use PDF;

class BalanceDueDateReminder extends BaseNotification
{
    protected $bookingClient;
    protected $client;
    protected $departureDate;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(BookingClient $bookingClient, Object $client, Object $departureDate)
    {
        $this->bookingClient = $bookingClient;
        $this->client = $client;
        $this->departureDate = $departureDate;
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
        $booking = $this->bookingClient->booking;
        $group = $booking->group;

        if ($group) {
            $subject = "{$group->bride_last_name} & {$group->groom_last_name}";
            $balance_due_date = $group->balance_due_date->format('F jS, Y');
            $name = $group->name;
            $supplierName = $group->provider ? $group->provider->name : '';
        } else {
            $subject = $name = $booking->full_name;
            $balance_due_date = $booking->balance_due_date ? $booking->balance_due_date->format('F jS, Y') : 'balance due date';
            $supplierName = $booking->provider ? $booking->provider->name : '';
        }

        return (new MailMessage)
            ->subject("{$subject} {$this->client->reservation_code} - Get ready to go")
            ->greeting(sprintf("T-%s DAYS", Carbon::today()->diffInDays($this->departureDate)))
            ->line('It’s not quite time to pack your bags, but it is time to finalize your booking and pay it in full.')
            ->line('On ' . $balance_due_date . ', we will automatically draft $ ' . sprintf('%0.2f', $this->client->total - $this->client->payments) . ' to your main card. This charge may appear on your credit card statement as ' . $supplierName . ' and not Barefoot Bridal. Please confirm all charges with us prior to initiating a dispute.')
            ->line('Any changes or cancellations must be made in writing prior to this date.')
            ->line('We know it\'s hard to keep track of the details, so we\'ve attached your invoice for reference. If you still have any questions, please let us know')
            ->attachData(
                PDF::loadView('pdf.invoice', ['invoice' => $booking->invoice])->output(), 
                'R' . $booking->order . ' BB Invoice - ' . $name . '.pdf',
                ['mime' => 'application/pdf']
            );
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