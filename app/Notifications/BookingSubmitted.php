<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Notifications\Messages\MailMessage;

class BookingSubmitted extends BaseNotification
{

    protected $booking;

    /**
     * Create a new notification instance.
     *
     * @param \App\Models\Booking $booking
     * @return void
     */
    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
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
        $subject_part = ($this->booking->group && !$this->booking->group->is_fit) ? 'booked' : 'asked for a quote';
        $working_line_word = ($this->booking->group && !$this->booking->group->is_fit) ? 'reservation' : 'quote';
        $asap_line_word = ($this->booking->group && !$this->booking->group->is_fit) ?  'booked' : 'the quotation';

        if ($this->booking->group) {
            $subject = "{$this->booking->group->bride_last_name} & {$this->booking->group->groom_last_name} {$this->booking->clients->first()->reservation_code} - Oh, hey, you’ve {$subject_part}";
            $action_text = 'Go To ' . $this->booking->group->name . '\'s Site';
            $action_route = route('couples', ['group' => $this->booking->group->slug]);
            $cc = $this->booking->group->travel_agent->email;
        } else {
            $subject = "{$this->booking->full_name} {$this->booking->clients->first()->reservation_code} - Oh, hey, you’ve {$subject_part}";
            $action_text = 'Go To Our Booking Site';
            $action_route = route('individual-bookings.page');
            $cc = config('emails.bookings');
        }

        return (new MailMessage)
            ->subject($subject)
            ->greeting('HANG TIGHT!')
            ->line("We're working on your {$working_line_word}, you don't have to do a thing!")
            ->line('We know our hotel partners run on Island Time. Don\'t worry, we don\'t.')
            ->line("We're doing everything we can to get you {$asap_line_word} as soon as possible.")
            ->line('Did you forget to add travel insurance? It\'s not too late. Trust us, it\'s worth it. Check out the deets <a href="https://www.tripmate.com/wpF785F/tic" target="__blank">here</a> and let us know.')
            ->action($action_text, $action_route)
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
