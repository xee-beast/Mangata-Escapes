<?php

namespace App\Notifications;

use App\Models\BookingClient;
use Illuminate\Notifications\Messages\MailMessage;
use PDF;

class BookingInvoiceFinal extends BaseNotification
{

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
        $transportable_guests = $this->bookingClient->guests()->where('transportation', true)->whereDoesntHave('flight_manifest')->exists();

        if ($group) {
            $name = $group->name;
            $subject_name = "{$group->bride_last_name} & {$group->groom_last_name}";
            $travelAgentEmail = $group->travel_agent->email;
        } else {
            $name = $subject_name = $booking->full_name;
            $travelAgentEmail = $booking->travel_agent ? $booking->travel_agent->email : config('emails.bookings');
        }

        $mailMessage = (new MailMessage)
            ->subject("{$subject_name} {$this->bookingClient->reservation_code} - Ready, set, sunshine")
            ->greeting('PACK YOUR BAGS')
            ->line(sprintf("<img src='%s' class='dancing-img' /><br />", asset('img/dancing.png')))
            ->line('You’re paid in full. The proof is attached.');

        if ($group && $group->transportation && $transportable_guests) {
            $mailMessage->line('Next Stop: Flights! Please get us your flights by ' . $group->transportation_submit_before->format('F j, Y') . '.');
        } elseif ($booking->transportation && $transportable_guests) {
            if ($booking->transportation_submit_before) {
                $mailMessage->line('Next Stop: Flights! Please get us your flights by ' . $booking->transportation_submit_before->format('F j, Y') . '.');
            } else {
                $mailMessage->line('Next Stop: Flights! Please get us your flights ASAP!.');
            }
        }

        $mailMessage->attachData(
            PDF::loadView('pdf.invoice', ['invoice' => $booking->invoice])->output(),
            'R' . $booking->order . ' BB Invoice - ' . $name . '.pdf',
            ['mime' => 'application/pdf']
        )
        ->cc(
            $booking->clients
            ->reject(function ($client) use ($notifiable) { return $client->client->id == $notifiable->id; })
            ->pluck('client.email')
            ->push($travelAgentEmail)
        );

        if ($group && !is_null($group->email)) {
            if (!is_null($group->secondary_email)) {
                $mailMessage->bcc([$group->email, $group->secondary_email]);
            } else {
                $mailMessage->bcc($group->email);
            }
        }

        return $mailMessage;
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
