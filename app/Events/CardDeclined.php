<?php

namespace App\Events;

use App\Models\Payment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CardDeclined
{
    use Dispatchable, SerializesModels;

    public $payment;

    /**
     * Create a new event instance.
     *
     * @param \App\Models\Payment $payment
     * @return void
     */
    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
    }
}
