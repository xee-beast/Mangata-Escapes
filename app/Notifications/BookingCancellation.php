<?php

namespace App\Notifications;

use App\Models\BookingClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;

class BookingCancellation extends Notification implements ShouldQueue
{
    use Queueable;

    protected $bookingClient;
    protected $cancellationType;

    const TYPE_BEFORE_NO_INSURANCE = 'before_no_insurance';
    const TYPE_BEFORE_WITH_INSURANCE = 'before_with_insurance';
    const TYPE_AFTER_NO_INSURANCE = 'after_no_insurance';
    const TYPE_AFTER_WITH_INSURANCE = 'after_with_insurance';

    /**
     * Create a new notification instance.
     *
     * @param BookingClient $bookingClient
     * @param string $cancellationType
     * @return void
     */
    public function __construct(BookingClient $bookingClient, string $cancellationType)
    {
        $this->bookingClient = $bookingClient;
        $this->cancellationType = $cancellationType;
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
        $booking = $this->bookingClient->bookingWithTrashed;
        $group = $booking->group;

        if ($group) {
            $brideName = $group->bride_first_name . ' ' . $group->bride_last_name;
            $groomName = $group->groom_first_name . ' ' . $group->groom_last_name;
            $coupleName = "{$brideName} & {$groomName}";
            $subject = "{$group->bride_last_name} & {$group->groom_last_name}";
        } else {
            $coupleName = $booking->full_name;
            $subject = $booking->full_name;
        }

        $mail = new MailMessage;
        $mail->subject("{$subject} {$this->bookingClient->reservation_code} - Cancellation Confirmation");

        switch ($this->cancellationType) {
            case self::TYPE_BEFORE_NO_INSURANCE:
                return $this->beforeNoInsuranceMail($mail, $coupleName);

            case self::TYPE_BEFORE_WITH_INSURANCE:
                return $this->beforeWithInsuranceMail($mail, $coupleName);

            case self::TYPE_AFTER_NO_INSURANCE:
                return $this->afterNoInsuranceMail($mail, $coupleName);

            case self::TYPE_AFTER_WITH_INSURANCE:
                return $this->afterWithInsuranceMail($mail, $coupleName);

            default:
                return $this->beforeNoInsuranceMail($mail, $coupleName);
        }
    }

    /**
     * Build email for cancellation before due date, no insurance
     *
     * @param MailMessage $mail
     * @param string $coupleName
     * @return MailMessage
     */
    protected function beforeNoInsuranceMail(MailMessage $mail, string $coupleName): MailMessage
    {
        return $mail
            ->greeting('Hi,')
            ->line("We're sorry to hear you will not be attending {$coupleName}'s wedding.")
            ->line('Per your request, your reservation has been cancelled.')
            ->line('')
            ->line('**You won\'t be charged again.**')
            ->line('There will be no additional charges applied to your card on file.')
            ->line('')
            ->line('**Eligible for a refund?**')
            ->line('Any refunds due will be posted back to your card on file within 7-10 business days.')
            ->line('')
            ->line('If you need to rebook at any time, we remain at your disposal!');
    }

    /**
     * Build email for cancellation before due date, with insurance
     *
     * @param MailMessage $mail
     * @param string $coupleName
     * @return MailMessage
     */
    protected function beforeWithInsuranceMail(MailMessage $mail, string $coupleName): MailMessage
    {
        return $mail
            ->greeting('Hi,')
            ->line("We're sorry to hear you will not be attending {$coupleName}'s wedding.")
            ->line('Per your request, your reservation has been cancelled.')
            ->line('')
            ->line('**You won\'t be charged again.**')
            ->line('There will be no additional charges applied to your card on file.')
            ->line('')
            ->line('**Eligible for a refund?**')
            ->line('Any refunds due will be posted back to your card on file within 7-10 business days.')
            ->line('Please note that the cost of travel insurance is non-refundable.')
            ->line('')
            ->line('If you need to rebook at any time, we remain at your disposal!');
    }

    /**
     * Build email for cancellation after due date, no insurance
     *
     * @param MailMessage $mail
     * @param string $coupleName
     * @return MailMessage
     */
    protected function afterNoInsuranceMail(MailMessage $mail, string $coupleName): MailMessage
    {
        return $mail
            ->greeting('Hi,')
            ->line("Oh no! We're really sorry that you will be unable to attend {$coupleName}'s wedding.")
            ->line('')
            ->line('This cancellation has been received after the deadline permitted for refunds and your reservation is not protected by travel insurance. Therefore, no refund is due.')
            ->line('')
            ->line('**Please confirm you still wish to cancel your reservation.**')
            ->line('')
            ->line('We will not request the cancellation until we receive your confirmation.')
            ->line('')
            ->line('Thank you!');
    }

    /**
     * Build email for cancellation after due date, with insurance
     *
     * @param MailMessage $mail
     * @param string $coupleName
     * @return MailMessage
     */
    protected function afterWithInsuranceMail(MailMessage $mail, string $coupleName): MailMessage
    {
        // CC groups@barefootbridal.com
        $mail->cc(config('emails.groups', 'groups@barefootbridal.com'));

        return $mail
            ->greeting('Hi,')
            ->line("Oh no! We're really sorry that you will be unable to attend {$coupleName}'s wedding.")
            ->line('')
            ->line('Thankfully, your reservation was protected with travel insurance!')
            ->line('')
            ->line('**How Do I File?**')
            ->line('Please allow up to 3-5 business days for a follow up email with instructions on how to file your claim.')
            ->line('')
            ->line('Thanks!');
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