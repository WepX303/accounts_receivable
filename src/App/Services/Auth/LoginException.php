<?php

namespace App\Services\Auth;

use RuntimeException;

/**
 * A sign-in that was refused. The message is translated; the field says which
 * input it belongs to and the reason tells the cases apart.
 */
class LoginException extends RuntimeException
{
    public function __construct(
        public readonly string $field,
        public readonly string $reason,
        string $message,
    ) {
        parent::__construct($message);
    }
}
