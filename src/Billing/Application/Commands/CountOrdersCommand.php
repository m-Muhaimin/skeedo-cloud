<?php

declare(strict_types=1);

namespace Billing\Application\Commands;

use Billing\Application\CommandHandlers\CountOrderCommandHandler;
use Billing\Domain\Entities\CouponEntity;
use Billing\Domain\Entities\PlanEntity;
use Billing\Domain\Entities\PlanSnapshotEntity;
use Billing\Domain\ValueObjects\BillingCycle;
use Billing\Domain\ValueObjects\OrderStatus;
use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;
use Workspace\Domain\Entities\WorkspaceEntity;

#[Handler(CountOrderCommandHandler::class)]
class CountOrdersCommand
{
    public ?OrderStatus $status = null;
    public null|Id|WorkspaceEntity $workspace = null;
    public null|Id|PlanEntity $plan = null;
    public null|Id|PlanSnapshotEntity $planSnapshot = null;
    public null|Id|CouponEntity $coupon = null;
    public ?BillingCycle $billingCycle = null;

    /** Search terms/query */
    public ?string $query = null;

    public function setStatus(string $status): self
    {
        $this->status = OrderStatus::from($status);

        return $this;
    }

    public function setWorkspace(string|Id|WorkspaceEntity $workspace): self
    {

        $this->workspace = is_string($workspace) ? new Id($workspace) : $workspace;
        return $this;
    }

    public function setPlan(string|Id|PlanEntity $plan): self
    {
        $this->plan = is_string($plan) ? new Id($plan) : $plan;
        return $this;
    }

    public function setCoupon(string|Id|CouponEntity $coupon): self
    {
        $this->coupon = is_string($coupon) ? new Id($coupon) : $coupon;
        return $this;
    }

    public function setPlanSnapshot(
        string|Id|PlanSnapshotEntity $planSnapshot
    ): self {
        $this->planSnapshot = is_string($planSnapshot)
            ? new Id($planSnapshot) : $planSnapshot;
        return $this;
    }

    public function setBillingCycle(string $billingCycle): self
    {
        $this->billingCycle = BillingCycle::from($billingCycle);
        return $this;
    }
}
