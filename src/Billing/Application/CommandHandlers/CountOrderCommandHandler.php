<?php

declare(strict_types=1);

namespace Billing\Application\CommandHandlers;

use Billing\Application\Commands\CountOrdersCommand;
use Billing\Domain\Repositories\OrderRepositoryInterface;

class CountOrderCommandHandler
{
    public function __construct(
        private OrderRepositoryInterface $repo
    ) {}

    public function handle(CountOrdersCommand $cmd): int
    {
        $orders = $this->repo;

        if ($cmd->status) {
            $orders = $orders->filterByStatus($cmd->status);
        }

        if ($cmd->workspace) {
            $orders = $orders->filterByWorkspace($cmd->workspace);
        }

        if ($cmd->plan) {
            $orders = $orders->filterByPlan($cmd->plan);
        }

        if ($cmd->coupon) {
            $orders = $orders->filterByCoupon($cmd->coupon);
        }

        if ($cmd->planSnapshot) {
            $orders = $orders->filterByPlanSnapshot($cmd->planSnapshot);
        }

        if ($cmd->billingCycle) {
            $orders = $orders->filterByBillingCycle($cmd->billingCycle);
        }

        if ($cmd->query) {
            $orders = $orders->search($cmd->query);
        }

        return $orders->count();
    }
}
