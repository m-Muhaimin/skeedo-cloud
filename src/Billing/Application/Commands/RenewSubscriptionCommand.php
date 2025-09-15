<?php

declare(strict_types=1);

namespace Billing\Application\Commands;

use Billing\Application\CommandHandlers\RenewSubscriptionCommandHandler;
use Billing\Domain\Entities\SubscriptionEntity;
use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;

#[Handler(RenewSubscriptionCommandHandler::class)]
class RenewSubscriptionCommand
{
    public Id|SubscriptionEntity $subscription;

    public function __construct(string|Id|SubscriptionEntity $subscription)
    {
        $this->subscription = is_string($subscription)
            ? new Id($subscription) : $subscription;
    }
}
