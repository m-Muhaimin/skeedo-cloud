<?php

namespace Stat\Infrastructure\Listeners;

use Billing\Domain\Events\CreditUsageEvent;
use Billing\Domain\Events\OrderCreatedEvent;
use Billing\Domain\Events\SubscriptionCreatedEvent;
use Stat\Domain\Entities\SignupStatEntity;
use Stat\Domain\Entities\OrderStatEntity;
use Stat\Domain\Entities\SubscriptionStatEntity;
use Stat\Domain\Entities\UsageStatEntity;
use Stat\Domain\Repositories\StatRepositoryInterface;
use Stat\Domain\ValueObjects\Metric;
use User\Domain\Events\UserCreatedEvent;

class SaveStat
{
    public function __construct(
        private StatRepositoryInterface $repo
    ) {
    }

    public function __invoke(
        CreditUsageEvent|UserCreatedEvent|SubscriptionCreatedEvent|OrderCreatedEvent $event
    ): void {
        match (true) {
            $event instanceof CreditUsageEvent =>
            $stat = new UsageStatEntity(
                $event->workspace,
                new Metric($event->count->value)
            ),

            $event instanceof UserCreatedEvent =>
            $stat = new SignupStatEntity(
                $event->user->getCountryCode()
            ),

            $event instanceof SubscriptionCreatedEvent =>
            $stat = new SubscriptionStatEntity(),

            $event instanceof OrderCreatedEvent =>
            $stat = new OrderStatEntity(),

            default => throw new \RuntimeException('Unknown event type'),
        };

        $this->repo->add($stat);
    }
}
