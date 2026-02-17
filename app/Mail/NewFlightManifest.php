<?php

namespace App\Mail;

use App\Models\BookingClient;
use App\Models\Guest;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewFlightManifest extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $recipient;
    public $bookingClient;
    public $dates_mismatch;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($recipient, BookingClient $bookingClient, $dates_mismatch)
    {
        $this->recipient = $recipient;
        $this->bookingClient = $bookingClient;
        $this->dates_mismatch = $dates_mismatch;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $mail = $this->from(config('emails.no_reply'), 'Barefoot Bridal');

        $booking =  $this->bookingClient->booking;
        $group = $booking->group;

        if ($group) {
            $transportation_submit_before = Carbon::parse($group->transportation_submit_before);
            $name = $group->name;
            $provider_id = $group->id_at_provider;
            $provider_name = $group->provider->name;
        } else {
            $transportation_submit_before =  $booking->transportation_submit_before ? Carbon::parse($booking->transportation_submit_before) : null;
            $name = $booking->full_name;
            $provider_id = $booking->id_at_provider;
            $provider_name = $booking->provider ? $booking->provider->name : null;
        }

        $subject = [];

        if ($transportation_submit_before && Carbon::today()->greaterThanOrEqualTo($transportation_submit_before)) {
            $subject[] = 'Late Submission';
        }

        $has_dates_mismatch = collect($this->bookingClient->guests)->some(function ($guest) {
            return !empty($guest->flight_manifest->arrival_date_mismatch_reason) ||
                    !empty($guest->flight_manifest->departure_date_mismatch_reason);
        });

        if (($this->dates_mismatch && !is_null($this->dates_mismatch)) || $has_dates_mismatch) {
            $subject[] = 'Dates Do not Match';
            $mail->cc(config('emails.groups'));
        }

        $has_manual = collect($this->bookingClient->guests)->some(function ($guest) {
            return $guest->flight_manifest && ($guest->flight_manifest->arrival_manual || $guest->flight_manifest->departure_manual);
        });

        if ($has_manual) {
            $subject[] = 'Flight Not Found';
        }

        if (count($subject) >= 2) {
            $provider_string = $provider_id ? ' - ' . $provider_id : '';
            $baseSubject = $name . $provider_string . ' - Flight Manifest Form - ' . implode(' & ', $subject);
        } else {
            if ($provider_name && $provider_id) {
                $provider_string = ' - ' . $provider_name . ' / ' . $provider_id;
            } else if ($provider_name) {
                $provider_string = ' - ' . $provider_name;
            } else if ($provider_id) {
                $provider_string = ' - ' . $provider_id;
            } else {
                $provider_string = '';
            }

            $baseSubject = $name . $provider_string . ' - Flight Manifest Form';

            if (count($subject) >= 1) {
                $baseSubject .= ' - ' . implode(' & ', $subject);
            }
        }

        $processedGuests = [];

        foreach ($this->bookingClient->guests->sortBy('id') as $guest) {
            $guestCheckIn = $guest->check_in;
            $guestCheckOut = $guest->check_out;

            if ($group) {
                $duplicateGuests = Guest::where('first_name', $guest->first_name)
                    ->where('last_name', $guest->last_name)
                    ->where('birth_date', $guest->birth_date->format('Y-m-d'))
                    ->whereHas('booking_client.booking', fn($q) => $q->where('group_id', $group->id))
                    ->get();

                if ($duplicateGuests->count() > 1) {
                    $guestCheckIn = $duplicateGuests->min('check_in');
                    $guestCheckOut = $duplicateGuests->max('check_out');
                }
            }

            $processedGuests[] = [
                'guest' => $guest,
                'check_in' => $guestCheckIn,
                'check_out' => $guestCheckOut,
            ];
        }

        return $mail->subject($baseSubject)->view('web.mail.newFlightManifest', ['processedGuests' => $processedGuests]);
    }
}
