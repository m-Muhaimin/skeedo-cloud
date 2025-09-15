<?php

declare(strict_types=1);

namespace Billing\Application\CommandHandlers;

use Billing\Application\Commands\ListCouponsCommand;
use Billing\Domain\Entities\CouponEntity;
use Billing\Domain\Exceptions\CouponNotFoundException;
use Billing\Domain\Repositories\CouponRepositoryInterface;
use Shared\Domain\ValueObjects\CursorDirection;
use Traversable;

class ListCouponsCommandHandler
{
    public function __construct(
        private CouponRepositoryInterface $repo
    ) {}

    /**
     * @return Traversable<CouponEntity>
     * @throws CouponNotFoundException
     */
    public function handle(ListCouponsCommand $cmd): Traversable
    {
        $cursor = $cmd->cursor
            ? $this->repo->ofId($cmd->cursor)
            : null;

        $coupons = $this->repo;

        if ($cmd->sortDirection) {
            $coupons = $coupons->sort($cmd->sortDirection, $cmd->sortParameter);
        }

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

        if ($cmd->maxResults) {
            $coupons = $coupons->setMaxResults($cmd->maxResults);
        }

        if ($cursor) {
            if ($cmd->cursorDirection == CursorDirection::ENDING_BEFORE) {
                return $coupons = $coupons->endingBefore($cursor);
            }

            return $coupons->startingAfter($cursor);
        }

        return $coupons;
    }
}
