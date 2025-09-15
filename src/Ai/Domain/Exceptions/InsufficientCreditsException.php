<?php

declare(strict_types=1);

namespace Ai\Domain\Exceptions;

use Exception;
use Throwable;

class InsufficientCreditsException extends Exception
{
    public function __construct(
        string $message = 'Insufficient credits to perform this operation.',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
