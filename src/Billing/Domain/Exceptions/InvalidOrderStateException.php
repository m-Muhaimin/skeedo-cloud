<?php

declare(strict_types=1);

namespace Billing\Domain\Exceptions;

use Billing\Domain\Entities\OrderEntity;
use Billing\Domain\ValueObjects\OrderStatus;
use Exception;
use Throwable;

class InvalidOrderStateException extends Exception
{
    public function __construct(
        public readonly OrderEntity $order,
        OrderStatus $status,
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            sprintf(
                "Order with status <%s> cannot be transitioned to <%s>! Order id: <%s>",
                $order->getStatus()->value,
                $status->value,
                $order->getId()->getValue()
            ),
            $code,
            $previous
        );
    }
}
