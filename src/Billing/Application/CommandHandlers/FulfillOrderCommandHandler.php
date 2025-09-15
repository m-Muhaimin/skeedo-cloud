<?php

declare(strict_types=1);

namespace Billing\Application\CommandHandlers;

use Billing\Application\Commands\FulfillOrderCommand;
use Billing\Domain\Entities\SubscriptionEntity;
use Billing\Domain\Events\OrderFulfilledEvent;
use Billing\Domain\Events\SubscriptionCreatedEvent;
use Billing\Domain\Exceptions\OrderNotFoundException;
use Billing\Domain\Exceptions\InvalidOrderStateException;
use Billing\Domain\Repositories\OrderRepositoryInterface;
use Easy\Container\Attributes\Inject;
use Psr\EventDispatcher\EventDispatcherInterface;
use Shared\Domain\ValueObjects\Id;
use Workspace\Domain\Entities\WorkspaceEntity;

class FulfillOrderCommandHandler
{
    public function __construct(
        private OrderRepositoryInterface $repo,
        private EventDispatcherInterface $dispatcher,

        #[Inject('option.billing.trial_without_payment')]
        private ?bool $trialWithoutPayment = null,
    ) {}

    /**
     * @throws OrderNotFoundException
     * @throws InvalidOrderStateException
     */
    public function handle(FulfillOrderCommand $cmd): SubscriptionEntity|WorkspaceEntity
    {
        $order = $cmd->order instanceof Id
            ? $this->repo->ofId($cmd->order) : $cmd->order;

        $plan = $order->getPlan();
        $ws = $order->getWorkspace();

        if ($plan->getBillingCycle()->isRenewable()) {
            $subs = SubscriptionEntity::createFromOrder($order);
            $ws->subscribe($subs);
            $order->fulfill();

            // Dispatch event
            $event = new OrderFulfilledEvent($order);
            $this->dispatcher->dispatch($event);

            if (
                $this->trialWithoutPayment
                && $order->getTrialPeriodDays()->value > 0
            ) {
                $subs->cancel();
            }

            // Dispatch event
            $event = new SubscriptionCreatedEvent($subs);
            $this->dispatcher->dispatch($event);

            return $subs;
        }

        $ws->addCredits($plan->getCreditCount());
        $order->fulfill();

        // Dispatch event
        $event = new OrderFulfilledEvent($order);
        $this->dispatcher->dispatch($event);

        return $ws;
    }
}
