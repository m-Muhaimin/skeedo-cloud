<?php

declare(strict_types=1);

namespace Billing\Application\Commands;

use Billing\Application\CommandHandlers\PayOrderCommandHandler;
use Billing\Domain\Entities\OrderEntity;
use Billing\Domain\ValueObjects\ExternalId;
use Billing\Domain\ValueObjects\PaymentGateway;
use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;

#[Handler(PayOrderCommandHandler::class)]
class PayOrderCommand
{
    public OrderEntity|Id $order;
    public PaymentGateway $gateway;
    public ExternalId $externalId;

    public function __construct(
        OrderEntity|Id|string $order,
        ?string $gateway = null,
        ?string $externalId = null
    ) {
        $this->order = is_string($order)
            ? new Id($order) : $order;

        $this->gateway = new PaymentGateway($gateway);
        $this->externalId = new ExternalId($externalId);
    }
}
