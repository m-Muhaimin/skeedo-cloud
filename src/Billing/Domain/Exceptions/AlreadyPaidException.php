<?php

declare(strict_types=1);

namespace Billing\Domain\Exceptions;

use Billing\Domain\Entities\OrderEntity;
use Exception;
use Throwable;

class AlreadyPaidException extends Exception
{
    public function __construct(
        public readonly OrderEntity $order,
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            sprintf(
                "Order with id <%s> is already paid!",
                $order->getId()->getValue()
            ),
            $code,
            $previous
        );
    }
}
