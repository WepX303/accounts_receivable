<?php

namespace App\Services\Payments;

use Carbon\Carbon;

class CreatePaymentData
{
    /**
     * @param  float  $received  the money handed over by the customer
     * @param  Carbon|null  $paymentAt  null when the payment is being entered as it happens
     */
    public function __construct(
        public readonly int $creditSourceId,
        public readonly string $method,
        public readonly float $received,
        public readonly float $cashTotal = 0.0,
        public readonly float $cardTotal = 0.0,
        public readonly ?string $receiverPhoneNumber = null,
        public readonly string $note = '',
        public readonly ?Carbon $paymentAt = null,
    ) {
    }

    public function at(?Carbon $paymentAt): static
    {
        return new static(
            $this->creditSourceId,
            $this->method,
            $this->received,
            $this->cashTotal,
            $this->cardTotal,
            $this->receiverPhoneNumber,
            $this->note,
            $paymentAt,
        );
    }
}
