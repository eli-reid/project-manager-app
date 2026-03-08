<?php

namespace App\Core\Cpanel\Exceptions;

use RuntimeException;

class CpanelRequestException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(string $message, public readonly array $context = [], int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
