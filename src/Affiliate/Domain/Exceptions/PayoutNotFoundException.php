<?php

declare(strict_types=1);

namespace Affiliate\Domain\Exceptions;

use Exception;
use Shared\Domain\ValueObjects\Id;
use Throwable;

class PayoutNotFoundException extends Exception
{
    public function __construct(
        public readonly Id $id,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        $message = sprintf(
            "Payout with id <%s> doesn't exists!",
            $id->getValue()
        );

        parent::__construct($message, $code, $previous);
    }
}
