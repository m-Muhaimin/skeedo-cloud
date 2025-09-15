<?php

declare(strict_types=1);

namespace Billing\Application\Commands;

use Billing\Application\CommandHandlers\FulfillOrderCommandHandler;
use Billing\Domain\Entities\OrderEntity;
use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;

#[Handler(FulfillOrderCommandHandler::class)]
class FulfillOrderCommand
{
    public OrderEntity|Id $order;

    public function __construct(
        OrderEntity|Id|string $order
    ) {
        $this->order = is_string($order)
            ? new Id($order) : $order;
    }
}
