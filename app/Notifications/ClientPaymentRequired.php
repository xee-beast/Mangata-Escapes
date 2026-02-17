<?php

namespace App\Notifications;

use App\Models\BookingClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClientPaymentRequired extends Notification implements ShouldQueue
{
    use Queueable;

    protected $bookingClient;
    protected $paymentDetails;
    protected $reason;

    public function __construct(BookingClient $bookingClient, array $paymentDetails, string $reason = 'Guest change request')
    {
        $this->bookingClient = $bookingClient;
        $this->paymentDetails = $paymentDetails;
        $this->reason = $reason;
    }

    public function via($notifiable) {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $group = $this->bookingClient->booking->group;
        $subject = "{$group->bride_last_name} & {$group->groom_last_name}";
        $couplesSiteUrl = route('couples', ['group' => $group->slug]);

        $amountToPay = $this->paymentDetails['amountToPay'] ?? 0;
        $isRefund = $amountToPay < 0;
        $amount = abs($amountToPay);

        if ($isRefund) {
            $mail = (new MailMessage)
                ->subject("{$subject} {$this->bookingClient->reservation_code} - Your reservation just became more affordable")
                ->greeting('ALMOST THERE!')
                ->line('We received a change request on your reservation that will result in a reduction of the total cost. Yay!')
                ->line("**Total refund due: $" . number_format($amount, 2) . "**")
                ->line('Sit back and relax, you don\'t need to do a thing! We will automatically process your refund once the required payment is received from the other parties on your reservation.')
                ->action('Go To ' . $group->name . '\'s Site', $couplesSiteUrl);
        } else {
            $mail = (new MailMessage)
                ->subject("{$subject} {$this->bookingClient->reservation_code} - Payment Required")
                ->greeting('ALMOST THERE!')
                ->line('We received a request to add you as a separate payer on a reservation, but need your payment to finalize the change.')
                ->line("**Total payment due: $" . number_format($amount, 2) . "**")
                ->line('Don\'t worry, it\'s easy! Use reservation code ' . "**" . $this->bookingClient->reservation_code . "**" . ' on the couple\'s website with the Make a Payment button to finalize the booking.')
                ->action('Go To ' . $group->name . '\'s Site', $couplesSiteUrl);
        }

        return $mail;
    }
}
