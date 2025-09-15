<?php

declare(strict_types=1);

namespace Billing\Domain\Repositories;

use Billing\Domain\Entities\CouponEntity;
use Billing\Domain\Entities\OrderEntity;
use Billing\Domain\Entities\PlanEntity;
use Billing\Domain\Entities\PlanSnapshotEntity;
use Billing\Domain\Exceptions\OrderNotFoundException;
use Billing\Domain\ValueObjects\BillingCycle;
use Billing\Domain\ValueObjects\OrderSortParameter;
use Billing\Domain\ValueObjects\OrderStatus;
use Iterator;
use Shared\Domain\Repositories\RepositoryInterface;
use Shared\Domain\ValueObjects\Id;
use Shared\Domain\ValueObjects\SortDirection;
use Workspace\Domain\Entities\WorkspaceEntity;

interface OrderRepositoryInterface extends RepositoryInterface
{
    /**
     * Add new entityt to the repository
     * 
     * @param OrderEntity $order
     * @return OrderRepositoryInterface
     */
    public function add(OrderEntity $order): self;

    /**
     * Find entity by id
     *
     * @param Id $id
     * @return OrderEntity
     * @throws OrderNotFoundException
     */
    public function ofId(Id $id): OrderEntity;

    /**
     * @param OrderStatus $status
     * @return static
     */
    public function filterByStatus(OrderStatus $status): static;

    /**
     * @param Id|WorkspaceEntity $workspace
     * @return static
     */
    public function filterByWorkspace(Id|WorkspaceEntity $workspace): static;

    /**
     * @param Id|PlanEntity $plan
     * @return static
     */
    public function filterByPlan(Id|PlanEntity $plan): static;

    /**
     * @param Id|CouponEntity $coupon
     * @return static
     */
    public function filterByCoupon(Id|CouponEntity $coupon): static;

    /**
     * @param Id|PlanSnapshotEntity $snapshot
     * @return static
     */
    public function filterByPlanSnapshot(
        Id|PlanSnapshotEntity $snapshot
    ): static;

    /**
     * @param BillingCycle $billingCycle
     * @return static
     */
    public function filterByBillingCycle(BillingCycle $billingCycle): static;

    /**
     * @param string $terms
     * @return static
     */
    public function search(string $terms): static;

    /**
     * @param SortDirection $dir
     * @param null|OrderSortParameter $sortParameter
     * @return static
     */
    public function sort(
        SortDirection $dir,
        ?OrderSortParameter $sortParameter = null
    ): static;

    /**
     * @param OrderEntity $cursor
     * @return Iterator<OrderEntity>
     */
    public function startingAfter(OrderEntity $cursor): Iterator;

    /**
     * @param OrderEntity $cursor
     * @return Iterator<OrderEntity>
     */
    public function endingBefore(OrderEntity $cursor): Iterator;
}
