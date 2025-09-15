<?php

declare(strict_types=1);

namespace Billing\Application\CommandHandlers;

use Billing\Application\Commands\UpdateCouponCommand;
use Billing\Domain\Entities\CouponEntity;
use Billing\Domain\Events\CouponUpdatedEvent;
use Billing\Domain\Exceptions\CouponNotFoundException;
use Billing\Domain\Repositories\CouponRepositoryInterface;
use Billing\Domain\Repositories\PlanRepositoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Shared\Domain\ValueObjects\Id;

class UpdateCouponCommandHandler
{
    public function __construct(
        private CouponRepositoryInterface $repo,
        private PlanRepositoryInterface $planRepo,
        private EventDispatcherInterface $dispatcher,
    ) {}

    /**
     * @throws CouponNotFoundException
     */
    public function handle(UpdateCouponCommand $cmd): CouponEntity
    {
        $coupon = $cmd->id instanceof CouponEntity
            ? $cmd->id
            : $this->repo->ofUniqueKey($cmd->id);

        if ($cmd->title) {
            $coupon->setTitle($cmd->title);
        }

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

        // Dispatch the coupon updated event
        $event = new CouponUpdatedEvent($coupon);
        $this->dispatcher->dispatch($event);

        return $coupon;
    }
}
