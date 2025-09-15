<?php

declare(strict_types=1);

namespace Billing\Application\CommandHandlers;

use Billing\Application\Commands\RenewSubscriptionCommand;
use Billing\Domain\Entities\SubscriptionEntity;
use Billing\Domain\Events\SubscriptionUsageResetEvent;
use Billing\Domain\Exceptions\NotDueException;
use Billing\Domain\Exceptions\SubscriptionNotFoundException;
use Billing\Domain\Repositories\SubscriptionRepositoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class RenewSubscriptionCommandHandler
{
    public function __construct(
        private SubscriptionRepositoryInterface $repo,
        private EventDispatcherInterface $dispatcher,
    ) {}

    /**
     * @throws SubscriptionNotFoundException
     */
    public function handle(RenewSubscriptionCommand $cmd): SubscriptionEntity
    {
        $sub = $cmd->subscription instanceof SubscriptionEntity
            ? $cmd->subscription
            : $this->repo->ofId($cmd->subscription);

        try {
            $sub->renew();

            // Dispatch the subscription usage reset event
            $event = new SubscriptionUsageResetEvent($sub);
            $this->dispatcher->dispatch($event);
        } catch (NotDueException $th) {
            // Subscription is not due for renewal
        }

        return $sub;
    }
}
