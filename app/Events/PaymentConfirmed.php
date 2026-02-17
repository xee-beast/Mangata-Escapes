<?php

namespace App\Events;

use \App\Models\Payment;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentConfirmed
{
    use Dispatchable, SerializesModels;

    public $payment;
    public $sendEmail;

    /**
     * Create a new event instance.
     *
     * @param \App\Models\Payment $payment
     * @return void
     */
    public function __construct(Payment $payment, bool $sendEmail)
    {
        $this->payment = $payment;
        $this->sendEmail = $sendEmail;
    }
}
