<?php

declare(strict_types=1);

namespace Billing\Application\CommandHandlers;

use Billing\Application\Commands\ListOrdersCommand;
use Billing\Domain\Entities\OrderEntity;
use Billing\Domain\Exceptions\OrderNotFoundException;
use Billing\Domain\Repositories\OrderRepositoryInterface;
use Shared\Domain\ValueObjects\CursorDirection;
use Traversable;

class ListOrdersCommandHandler
{
    public function __construct(
        private OrderRepositoryInterface $repo,
    ) {}

    /**
     * @return Traversable<OrderEntity>
     * @throws OrderNotFoundException
     */
    public function handle(ListOrdersCommand $cmd): Traversable
    {
        $cursor = $cmd->cursor
            ? $this->repo->ofId($cmd->cursor)
            : null;

        $orders = $this->repo;

        if ($cmd->sortDirection) {
            $orders = $orders->sort($cmd->sortDirection, $cmd->sortParameter);
        }

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

        if ($cmd->maxResults) {
            $orders = $orders->setMaxResults($cmd->maxResults);
        }

        if ($cursor) {
            if ($cmd->cursorDirection == CursorDirection::ENDING_BEFORE) {
                return $orders = $orders->endingBefore($cursor);
            }

            return $orders->startingAfter($cursor);
        }

        return $orders;
    }
}
