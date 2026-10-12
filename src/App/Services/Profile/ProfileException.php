<?php

namespace App\Services\Profile;

use RuntimeException;

/**
 * A profile change that was refused; the field says which input the message belongs to.
 */
class ProfileException extends RuntimeException
{
    public function __construct(public readonly string $field, string $message)
    {
        parent::__construct($message);
    }
}
