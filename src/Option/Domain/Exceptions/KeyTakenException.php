<?php

declare(strict_types=1);

namespace Option\Domain\Exceptions;

use Exception;
use Option\Domain\ValueObjects\Key;
use Throwable;

class KeyTakenException extends Exception
{
    public function __construct(
        public readonly Key $key,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct(
            sprintf(
                "Option with key %s is already taken!",
                $key->value
            ),
            $code,
            $previous
        );
    }
}
