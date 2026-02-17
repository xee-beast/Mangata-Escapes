<?php

namespace App\Events;

use App\Models\Payment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentSubmitted
{
    use Dispatchable, SerializesModels;

    public $payment;
    public $signedInsurance;
    public $type;

    /**
     * Create a new event instance.
     *
     * @param \App\Models\Payment $payment
     * @param boolean $signedInsurance
     * @return void
     */
    public function __construct(Payment $payment, $signedInsurance = false, $type)
    {
        $this->payment = $payment;
        $this->signedInsurance = $signedInsurance;
        $this->type = $type;
    }
}
