<?php

namespace App\Notifications;

use App\Models\BookingClient;
use Illuminate\Notifications\Messages\MailMessage;
use PDF;
use Illuminate\Support\Str;

class BookingInvoice extends BaseNotification
{
    protected $bookingClient;
    protected $client_names;

    /**
     * Create a new notification instance.
     *
     * @param \App\Models\Booking $booking
     * @return void
     */
    public function __construct(BookingClient $bookingClient)
    {
        $this->bookingClient = $bookingClient;
        $client_names = '';

        foreach($bookingClient->booking->clients as $key => $client) {
            if ($key) {
                $client_names .= $key < ($bookingClient->booking->clients->count() - 1) ? ', ' : ' & ';
            }

            $client_names .= $client->first_name;
        }

        $this->client_names = $client_names;
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
            $name = $group->name;
            $subject_name = "{$group->bride_last_name} & {$group->groom_last_name}";
            $route = route('couples', ['group' => $group->slug]);
            $travelAgentEmail = $group->travel_agent->email;
        } else {
            $name = $subject_name = $booking->full_name;
            $route = route('individual-bookings.page');
            $travelAgentEmail = $booking->travel_agent ? $booking->travel_agent->email : config('emails.bookings');
        }
        
        $message = (new MailMessage)
            ->subject("{$subject_name} {$this->bookingClient->reservation_code} - It's official")
            ->greeting('YOU\'RE BOOKED')
            ->line(Str::upper($this->client_names) . ',')
            ->line('Do you want to make a change to your booking? Want to set up auto pay? Any other questions? Respond to this email and we will help!')
            ->line('Need to update your card on file or make a payment? No problem, use your reservation code <b>' . $this->bookingClient->reservation_code . '</b> <a href="' . $route . '" target="__blank">here</b>.')
            ->line('Now is a good time to check if you have a passport valid for at least 6 months after your return.')
            ->line('The countdown begins!')
            ->line('<i>PS: Your invoice is attached.</i>')
            ->attachData(
                PDF::loadView('pdf.invoice', ['invoice' => $booking->invoice])->output(), 
                'R' . $booking->order . ' BB Invoice - ' . $name . '.pdf',
                ['mime' => 'application/pdf'])
            ->cc(
                $booking->clients
                ->reject(function ($client) use ($notifiable) { return $client->client->id == $notifiable->id; })
                ->pluck('client.email')
                ->push($travelAgentEmail)
            );

        if ($group && !is_null($group->email)) {
            if (!is_null($group->secondary_email)) {
                $message->bcc([$group->email, $group->secondary_email]);
            } else {
                $message->bcc($group->email);
            }
        }

        return $message;
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
