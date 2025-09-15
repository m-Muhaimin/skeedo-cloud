<?php

declare(strict_types=1);

namespace Billing\Application\Commands;

use Billing\Application\CommandHandlers\CountCouponsCommandHandler;
use Billing\Domain\Entities\PlanEntity;
use Billing\Domain\ValueObjects\BillingCycle;
use Billing\Domain\ValueObjects\DiscountType;
use Billing\Domain\ValueObjects\Status;
use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;

#[Handler(CountCouponsCommandHandler::class)]
class CountCouponsCommand
{
    public ?Status $status = null;
    public ?BillingCycle $billingCycle = null;
    public ?DiscountType $discountType = null;
    public null|Id|PlanEntity $plan = null;

    /** Search terms/query */
    public ?string $query = null;

    public function setStatus(int $status): self
    {
        $this->status = Status::from($status);
        return $this;
    }

    public function setBillingCycle(string $billingCycle): self
    {
        $this->billingCycle = BillingCycle::from($billingCycle);
        return $this;
    }

    public function setDiscountType(string $discountType): self
    {
        $this->discountType = DiscountType::from($discountType);
        return $this;
    }

    public function setPlan(string|Id|PlanEntity $plan): self
    {
        $this->plan = is_string($plan) ? new Id($plan) : $plan;
        return $this;
    }
}
