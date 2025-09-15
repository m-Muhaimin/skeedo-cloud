<?php

declare(strict_types=1);

namespace Billing\Application\Commands;

use Billing\Application\CommandHandlers\CancelSubscriptionCommandHandler;
use Billing\Domain\Entities\SubscriptionEntity;
use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;

/**
 * Cancels the active subscription of the workspace.
 * This doesn't downgrade the plan, it just schedules the cancellation
 * of the subscription at the end of the current billing period.
 */
#[Handler(CancelSubscriptionCommandHandler::class)]
class CancelSubscriptionCommand
{
    public Id|SubscriptionEntity $subscription;

    public function __construct(
        string|Id|SubscriptionEntity $subscription,
    ) {
        $this->subscription = is_string($subscription)
            ? new Id($subscription) : $subscription;
    }
}
