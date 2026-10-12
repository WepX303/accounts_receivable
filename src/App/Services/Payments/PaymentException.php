<?php

namespace App\Services\Payments;

use RuntimeException;

/**
 * A payment that was refused. The message is already translated and safe to
 * show; the code is for callers that need to tell the reasons apart.
 */
class PaymentException extends RuntimeException
{
    public function __construct(public readonly string $errorCode, string $message)
    {
        parent::__construct($message);
    }
}
