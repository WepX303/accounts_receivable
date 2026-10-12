<?php

namespace App\Services\Payments;

use Carbon\Carbon;

class CorrectPaymentData
{
    /**
     * @param  float  $received  the money handed over by the customer
     * @param  Carbon|null  $paymentAt  null to date the corrected payment now
     */
    public function __construct(
        public readonly string $method,
        public readonly float $received,
        public readonly float $cashTotal = 0.0,
        public readonly float $cardTotal = 0.0,
        public readonly string $note = '',
        public readonly string $reason = '',
        public readonly ?Carbon $paymentAt = null,
    ) {
    }
}
