<?php

declare(strict_types=1);

namespace Billing\Application\CommandHandlers;

use Billing\Application\Commands\CreateOrderCommand;
use Billing\Domain\Entities\CouponEntity;
use Billing\Domain\Entities\OrderEntity;
use Billing\Domain\Events\OrderCreatedEvent;
use Billing\Domain\Exceptions\PlanNotFoundException;
use Billing\Domain\Repositories\CouponRepositoryInterface;
use Billing\Domain\Repositories\OrderRepositoryInterface;
use Billing\Domain\Repositories\PlanRepositoryInterface;
use Billing\Domain\ValueObjects\TrialPeriodDays;
use Easy\Container\Attributes\Inject;
use Psr\EventDispatcher\EventDispatcherInterface;
use Shared\Domain\ValueObjects\CurrencyCode;
use Shared\Domain\ValueObjects\Id;
use Workspace\Domain\Exceptions\WorkspaceNotFoundException;
use Workspace\Infrastructure\Repositories\DoctrineOrm\WorkspaceRepository;

class CreateOrderCommandHandler
{
    public function __construct(
        private OrderRepositoryInterface $orepo,
        private WorkspaceRepository $wrepo,
        private PlanRepositoryInterface $prepo,
        private CouponRepositoryInterface $crepo,
        private EventDispatcherInterface $dispatcher,

        #[Inject('option.billing.trial_period_days')]
        private ?int $trialPeriodDays = null,

        #[Inject('option.billing.trial_without_payment')]
        private ?bool $trialWithoutPayment = null,

        #[Inject('option.billing.currency')]
        private ?string $currency = null,
    ) {}

    /**
     * @param CreateOrderCommand $cmd
     * @return OrderEntity
     * @throws WorkspaceNotFoundException
     * @throws PlanNotFoundException
     */
    public function handle(CreateOrderCommand $cmd): OrderEntity
    {
        $ws = $cmd->workspace instanceof Id
            ? $this->wrepo->ofId($cmd->workspace) : $cmd->workspace;

        $plan = $cmd->plan instanceof Id
            ? $this->prepo->ofId($cmd->plan) : $cmd->plan;

        $coupon = null;

        if ($cmd->coupon) {
            $coupon = $cmd->coupon instanceof CouponEntity
                ? $cmd->coupon
                : $this->crepo->ofUniqueKey($cmd->coupon);
        }

        $order = new OrderEntity(
            $ws,
            $plan,
            CurrencyCode::tryFrom($this->currency) ?? CurrencyCode::USD,
            new TrialPeriodDays($this->trialPeriodDays),
            $coupon
        );

        if (
            $this->trialWithoutPayment
            && $order->getTrialPeriodDays()->value > 0
        ) {
            // Mark the order as paid
            $order->pay();
        }

        if ($order->getTotalPrice()->value === 0) {
            $order->pay();
        }

        $this->orepo->add($order);

        // Dispatch the order created event
        $event = new OrderCreatedEvent($order);
        $this->dispatcher->dispatch($event);

        return $order;
    }
}
