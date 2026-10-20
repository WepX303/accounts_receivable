<?php

namespace App\Services\Contracts;

use RuntimeException;

/**
 * A contract action that was refused. The message is already translated and
 * safe to show; the code is for callers that need to tell the reasons apart.
 */
class ContractException extends RuntimeException
{
    public function __construct(public readonly string $errorCode, string $message)
    {
        parent::__construct($message);
    }
}
