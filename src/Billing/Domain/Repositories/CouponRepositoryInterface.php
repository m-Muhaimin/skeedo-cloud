<?php

declare(strict_types=1);

namespace Billing\Domain\Repositories;

use Billing\Domain\Entities\CouponEntity;
use Billing\Domain\Entities\PlanEntity;
use Billing\Domain\Exceptions\CouponNotFoundException;
use Billing\Domain\ValueObjects\BillingCycle;
use Billing\Domain\ValueObjects\Code;
use Billing\Domain\ValueObjects\CouponSortParameter;
use Billing\Domain\ValueObjects\DiscountType;
use Billing\Domain\ValueObjects\Status;
use Iterator;
use Shared\Domain\Repositories\RepositoryInterface;
use Shared\Domain\ValueObjects\Id;
use Shared\Domain\ValueObjects\SortDirection;

interface CouponRepositoryInterface extends RepositoryInterface
{
    /**
     * Add new entity to the repository
     * 
     * @param CouponEntity $coupon
     * @return CouponRepositoryInterface
     */
    public function add(CouponEntity $coupon): static;

    /**
     * Remove the entity from the repository
     * 
     * @param CouponEntity $coupon
     * @return CouponRepositoryInterface
     */
    public function remove(CouponEntity $coupon): static;

    /**
     * Find entity by id
     * 
     * @param Id $id
     * @return CouponEntity
     * @throws CouponNotFoundException
     */
    public function ofId(Id $id): CouponEntity;

    /**
     * Find entity by code
     * 
     * @param Code $code
     * @return CouponEntity
     * @throws CouponNotFoundException
     */
    public function ofCode(Code $code): CouponEntity;

    /**
     * Find entity by unique key
     * 
     * @param Code|Id $key
     * @return CouponEntity
     * @throws CouponNotFoundException
     */
    public function ofUniqueKey(Code|Id $key): CouponEntity;

    /**
     * Filter by status
     * @param Status $status
     * @return CouponRepositoryInterface
     */
    public function filterByStatus(Status $status): static;

    /**
     * Filter by billing cycle
     * 
     * @param BillingCycle $billingCycle
     * @return CouponRepositoryInterface
     */
    public function filterByBillingCycle(BillingCycle $billingCycle): static;

    /**
     * Filter by discount type
     * 
     * @param DiscountType $discountType
     * @return CouponRepositoryInterface
     */
    public function filterByDiscountType(DiscountType $discountType): static;

    /**
     * Filter by plan
     * 
     * @param Id|PlanEntity $plan
     * @return CouponRepositoryInterface
     */
    public function filterByPlan(Id|PlanEntity $plan): static;

    /**
     * Search by terms
     * 
     * @param string $terms
     * @return CouponRepositoryInterface
     */
    public function search(string $terms): static;

    /**
     * Sort by direction and parameter
     * 
     * @param SortDirection $dir
     * @param null|CouponSortParameter $sortParameter
     * @return static
     */
    public function sort(
        SortDirection $dir,
        ?CouponSortParameter $sortParameter = null
    ): static;

    /**
     * Starting after cursor
     * 
     * @param CouponEntity $cursor
     * @return Iterator<CouponEntity>
     */
    public function startingAfter(CouponEntity $cursor): Iterator;

    /**
     * Ending before cursor
     * 
     * @param CouponEntity $cursor
     * @return Iterator<CouponEntity>
     */
    public function endingBefore(CouponEntity $cursor): Iterator;
}
