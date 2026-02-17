<?php

namespace App\Notifications;

use App\Models\BookingClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FlightManifestSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    protected $bookingClient;

    /**
     * Create a new notification instance.
     *
     * @param \App\Models\BookingClient $bookingClient
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
        if ($this->bookingClient->booking->group) {
            $submit_before = $this->bookingClient->booking->group->transportation_submit_before->startOfDay();
            $subject = "{$this->bookingClient->booking->group->bride_last_name} & {$this->bookingClient->booking->group->groom_last_name}";
        } else {
            $submit_before = $this->bookingClient->booking->transportation_submit_before ? $this->bookingClient->booking->transportation_submit_before->startOfDay() : null;
            $subject = $this->bookingClient->booking->full_name;
        }

        $now = now()->startOfDay();

        if ($submit_before) {
            if ($now->greaterThan($submit_before->copy()->addDays(5))) {
                $deadline = $now->format('F jS, Y');
            } elseif ($now->greaterThan($submit_before)) {
                $deadline = $submit_before->copy()->addDays(5)->format('F jS, Y');
            } else {
                $deadline = $submit_before->format('F jS, Y');
            }
        } else {
            $deadline = 'the submission date';
        }

        $mail = (new MailMessage)
            ->subject("{$subject} {$this->bookingClient->reservation_code} - Prepare For Landing")
            ->greeting('FASTEN YOUR SEATBELTS')
            ->line('Flight itineraries were successfully uploaded for the following guest(s):');
        
        foreach ($this->bookingClient->guests->filter(fn($guest) => $guest->flight_manifest) as $guest) {
            $mail->line('• ' . $guest->first_name . ' ' . $guest->last_name);
        }

        $mail->line('You will receive an email with further instructions on how to find your transportation company upon landing.');

        $missing_guests = $this->bookingClient->guests->filter(
            fn($guest) => $guest->transportation && !$guest->flight_manifest
        );

        if ($missing_guests->isNotEmpty()) {
            $mail->line('NOTE! The following guest(s) are missing flight itineraries and will not have transfers scheduled if not uploaded by ' . $deadline . ':');

            foreach ($missing_guests as $guest) {
                $mail->line('• ' . $guest->first_name . ' ' . $guest->last_name);
            }
        }
            
        $mail->bcc('bfbtransfers@gmail.com');  
            
        return $mail;
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
