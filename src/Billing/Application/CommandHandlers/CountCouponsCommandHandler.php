<?php

declare(strict_types=1);

namespace Billing\Application\CommandHandlers;

use Billing\Application\Commands\CountCouponsCommand;
use Billing\Domain\Repositories\CouponRepositoryInterface;

class CountCouponsCommandHandler
{
    public function __construct(
        private CouponRepositoryInterface $repo
    ) {}

    public function handle(CountCouponsCommand $cmd): int
    {
        $coupons = $this->repo;

        if ($cmd->status) {
            $coupons = $coupons->filterByStatus($cmd->status);
        }

        if ($cmd->billingCycle) {
            $coupons = $coupons->filterByBillingCycle($cmd->billingCycle);
        }

        if ($cmd->discountType) {
            $coupons = $coupons->filterByDiscountType($cmd->discountType);
        }

        if ($cmd->plan) {
            $coupons = $coupons->filterByPlan($cmd->plan);
        }

        if ($cmd->query) {
            $coupons = $coupons->search($cmd->query);
        }

        return $coupons->count();
    }
}
