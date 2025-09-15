<?php

declare(strict_types=1);

namespace Option\Domain\Exceptions;

use Exception;
use Option\Domain\ValueObjects\Key;
use Shared\Domain\ValueObjects\Id;
use Throwable;

class OptionNotFoundException extends Exception
{
    public function __construct(
        public readonly Id|Key $id,
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        $message = $id instanceof Key
            ? sprintf(
                "Option with key <%s> doesn't exists!",
                $id->value
            )
            : sprintf(
                "Option with id <%s> doesn't exists!",
                $id->getValue()
            );

        parent::__construct(
            $message,
            $code,
            $previous
        );
    }
}
