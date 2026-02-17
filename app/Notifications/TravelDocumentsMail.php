<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Barryvdh\DomPDF\Facade\Pdf as FacadePdf;

class TravelDocumentsMail extends Notification
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
            $transportation = $this->booking->group->transportation;
            $subject = "{$this->booking->group->bride_last_name} & {$this->booking->group->groom_last_name}";
            $name = $this->booking->group->name;
        } else {
            $transportation = $this->booking->transportation;
            $name = $subject = $this->booking->full_name;
        }

        $guests = $this->booking->guests;
        $guests_count = $guests->count();
        $guests_with_transportation = $guests->filter(fn($guest) => $guest->transportation);
        $guests_with_transportation_count = $guests_with_transportation->count();
        $all_guests_have_transportation = false;
        $some_guests_have_transportation = false;
        $all_guests_have_manifests = false;
        $some_guests_have_manifests = false;
        $show_transportation_line = false;

        if ($transportation && $guests_with_transportation_count === $guests_count) {
            $all_guests_have_transportation = true;
        } elseif ($transportation && $guests_with_transportation_count > 0) {
            $some_guests_have_transportation = true;
        }

        if ($transportation && $guests_with_transportation_count > 0) {
            $guests_with_manifest = $guests_with_transportation->filter(fn($guest) => $guest->flight_manifest !== null);
            $guests_with_manifest_count = $guests_with_manifest->count();

            if ($guests_with_manifest_count >= $guests_with_transportation_count) {
                $all_guests_have_manifests = true;
            } elseif ($guests_with_manifest_count > 0) {
                $some_guests_have_manifests = true;
            }
        }

        if (!$all_guests_have_transportation && !$some_guests_have_transportation) {
            $transportation_note = "You do not have airport transfers set up via Barefoot Bridal!";
        } else {
            if (!$all_guests_have_manifests && !$some_guests_have_manifests) {
                $transportation_note = "You do not have airport transfers set up via Barefoot Bridal!";
            } elseif ($all_guests_have_manifests) {
                $transportation_note = "You have airport transfers set up via Barefoot Bridal!";
                $show_transportation_line = true;
            } elseif ($some_guests_have_manifests) {
                $show_transportation_line = true;
                $transportation_note = "You failed to submit flight information for all of the guests and therefore do not have airport transfers set up for everyone via Barefoot Bridal!";
            }
        }

        $mail = (new MailMessage)
            ->subject("{$subject} - Your Travel Documents")
            ->greeting('IMPORTANT!');

        if ($show_transportation_line) {
            $mail->line('- Airport Transfers');
        }

        $mail->line('- In-Travel Concerns')
            ->line('- Visa and Tax Information')
            ->line('- Things to Know Before You Go')
            ->line('- ... and so much more information is attached to this email.')
            ->line('Download the attachment to your phone and keep it handy in case you do not have WiFi to access it when needed!')
            ->line('Note: ' . $transportation_note);
        
        $guests = $this->booking->guests->filter(fn ($guest) => $guest->flight_manifest && $guest->transportation);

        $grouped_guests = $guests->groupBy(function ($guest) {
            $manifest = $guest->flight_manifest;

            return md5(json_encode([
                'arrival_datetime' => optional($manifest->arrival_datetime)->format('Y-m-d H:i'),
                'departure_datetime' => optional($manifest->departure_datetime)->format('Y-m-d H:i'),
                'arrival_airline' => $manifest->arrival_airline,
                'arrival_number' => $manifest->arrival_number,
                'departure_airline' => $manifest->departure_airline,
                'departure_number' => $manifest->departure_number,
                'arrival_airport' => optional($manifest->arrivalAirport)->id,
                'departure_airport' => optional($manifest->departureAirport)->id,
                'pickup_time' => $guest->departure_pickup_time,
            ]));
        });

        $bookingService = app(BookingService::class);
        $processedGuests = $bookingService->handelTravelDocumentsDuplicateGuests($this->booking->group, $this->booking);

        $mail->attachData(
                FacadePdf::loadView('pdf.travel-documents', [
                    'group' => $this->booking->group ? $this->booking->group->load('provider', 'destination', 'airports.transfer') : null,
                    'booking' => $this->booking->load('guests.flight_manifest', 'roomArrangements', 'roomBlocks.room.hotel', 'provider', 'destination', 'transfer'),
                    'hotel' => $this->booking->group ? $this->booking->roomBlocks->first()->hotel_block->hotel : null,
                    'grouped_guests' => $grouped_guests,
                    'processed_guests' => $processedGuests,
                ])->output(),
                'Travel Documents - ' . $name . ' - ' . $this->booking->order . '.pdf',
                ['mime' => 'application/pdf']
            );
        
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
