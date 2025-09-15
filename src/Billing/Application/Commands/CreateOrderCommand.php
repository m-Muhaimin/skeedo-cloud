<?php

declare(strict_types=1);

namespace Billing\Application\Commands;

use Billing\Application\CommandHandlers\CreateOrderCommandHandler;
use Billing\Domain\Entities\CouponEntity;
use Billing\Domain\Entities\PlanEntity;
use Billing\Domain\Entities\PlanSnapshotEntity;
use Billing\Domain\ValueObjects\Code;
use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;
use Throwable;
use Workspace\Domain\Entities\WorkspaceEntity;

#[Handler(CreateOrderCommandHandler::class)]
class CreateOrderCommand
{
    public WorkspaceEntity|Id $workspace;
    public PlanEntity|PlanSnapshotEntity|Id $plan;
    public null|Id|Code|CouponEntity $coupon = null;

    public function __construct(
        WorkspaceEntity|Id|string $workspace,
        PlanEntity|PlanSnapshotEntity|Id|string $plan
    ) {
        $this->workspace = is_string($workspace)
            ? new Id($workspace) : $workspace;

        $this->plan = is_string($plan)
            ? new Id($plan) : $plan;
    }

    public function setCoupon(string|Id|Code|CouponEntity $coupon): void
    {
        if (is_string($coupon)) {
            try {
                $this->coupon = new Id($coupon);
            } catch (Throwable $e) {
                $this->coupon = new Code($coupon);
            }
        } else {
            $this->coupon = $coupon;
        }
    }
}
