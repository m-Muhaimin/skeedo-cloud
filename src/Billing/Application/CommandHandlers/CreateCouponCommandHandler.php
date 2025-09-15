<?php

declare(strict_types=1);

namespace Billing\Application\CommandHandlers;

use Billing\Application\Commands\CreateCouponCommand;
use Billing\Domain\Entities\CouponEntity;
use Billing\Domain\Events\CouponCreatedEvent;
use Billing\Domain\Repositories\CouponRepositoryInterface;
use Billing\Domain\Repositories\PlanRepositoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Shared\Domain\ValueObjects\Id;

class CreateCouponCommandHandler
{
    public function __construct(
        private CouponRepositoryInterface $repo,
        private PlanRepositoryInterface $planRepo,
        private EventDispatcherInterface $dispatcher,
    ) {}

    public function handle(CreateCouponCommand $cmd): CouponEntity
    {
        $coupon = new CouponEntity(
            $cmd->title,
            $cmd->code,
            $cmd->discountType,
            $cmd->amount,
            $cmd->cycleCount,
        );

        if ($cmd->billingCycle !== false) {
            $coupon->setBillingCycle($cmd->billingCycle);
        }

        if ($cmd->redemptionLimit) {
            $coupon->setRedemptionLimit($cmd->redemptionLimit);
        }

        if ($cmd->plan !== false) {
            $plan = $cmd->plan;

            if ($plan instanceof Id) {
                $plan = $this->planRepo->ofId($plan);
            }

            $coupon->setPlan($plan);
        }

        if ($cmd->status) {
            $coupon->setStatus($cmd->status);
        }

        if ($cmd->startsAt !== false) {
            $coupon->setStartsAt($cmd->startsAt);
        }

        if ($cmd->expiresAt !== false) {
            $coupon->setExpiresAt($cmd->expiresAt);
        }

        $this->repo->add($coupon);

        // Dispatch the coupon created event
        $event = new CouponCreatedEvent($coupon);
        $this->dispatcher->dispatch($event);

        return $coupon;
    }
}
