<?php

declare(strict_types=1);

namespace User\Domain\Exceptions;

use Exception;
use Throwable;
use User\Domain\ValueObjects\Email;

class EmailTakenException extends Exception
{
    public function __construct(
        public readonly Email $email,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct(
            sprintf(
                "Email %s is already taken!",
                $email->value
            ),
            $code,
            $previous
        );
    }
}
