<?php

namespace App\Notifications;

use App\Models\BookingClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FlightManifestRequest extends Notification implements ShouldQueue
{
    use Queueable;

    protected $bookingClient;
    protected $guestsWithoutManifests;

    /**
     * Create a new notification instance.
     *
     * @param \App\Models\BookingClient $bookingClient
     * @return void
     */
    public function __construct(BookingClient $bookingClient, $guestsWithoutManifests)
    {
        $this->bookingClient = $bookingClient;
        $this->guestsWithoutManifests = $guestsWithoutManifests;
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
        $guestNames = collect($this->guestsWithoutManifests)->map(function ($guest) {
            return $guest->first_name . ' ' . $guest->last_name;
        })->values()->all();

        if (count($guestNames) === 1) {
            $guestLine = "We have not received a flight itinerary for {$guestNames[0]}.";
            $guestLine2 = "We need to schedule airport transfers for this guest.";
        } elseif (count($guestNames) === 2) {
            $guestLine = "We have not received a flight itinerary for {$guestNames[0]} and {$guestNames[1]}.";
            $guestLine2 = "We need to schedule airport transfers for these guests.";
        } else {
            $lastGuest = array_pop($guestNames);
            $guestLine = 'We have not received a flight itinerary for ' . implode(', ', $guestNames) . ', and ' . $lastGuest . '.';
            $guestLine2 = 'We need to schedule airport transfers for these guests.';
        }

        $booking = $this->bookingClient->booking;
        $group = $booking->group;

        if ($group) {
            $subject = "{$group->bride_last_name} & {$group->groom_last_name}";
            $action = 'Go To ' . $group->name . '\'s Site';
            $route = route('couples', ['group' => $group->slug]);
            $transportation_date = $group->transportation_submit_before->format('F jS, Y');
        } else {
            $subject = $booking->full_name;
            $action = 'Go To Our Booking Site';
            $route = route('individual-bookings.page');
            $transportation_date = $booking->transportation_submit_before ? $booking->transportation_submit_before->format('F jS, Y') : 'the submission date';
        }

        return (new MailMessage)
            ->subject("{$subject} {$this->bookingClient->reservation_code} - Prepare for Takeoff")
            ->greeting('3, 2, 1…')
            ->line($guestLine)
            ->line($guestLine2)
            ->line('Upload their flight itinerary here using your reservation code <b>' . $this->bookingClient->reservation_code . '</b> by ' . $transportation_date . '.')
            ->action($action, $route)
            ->bcc(config('emails.bfb_transfers'));
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
