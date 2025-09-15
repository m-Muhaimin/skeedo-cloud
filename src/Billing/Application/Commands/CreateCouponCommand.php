<?php

declare(strict_types=1);

namespace Billing\Application\Commands;

use Billing\Application\CommandHandlers\CreateCouponCommandHandler;
use Billing\Domain\Entities\PlanEntity;
use Billing\Domain\ValueObjects\Amount;
use Billing\Domain\ValueObjects\BillingCycle;
use Billing\Domain\ValueObjects\Code;
use Billing\Domain\ValueObjects\Count;
use Billing\Domain\ValueObjects\DiscountType;
use Billing\Domain\ValueObjects\Status;
use Billing\Domain\ValueObjects\Title;
use DateTime;
use DateTimeInterface;
use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;

#[Handler(CreateCouponCommandHandler::class)]
class CreateCouponCommand
{
    public Title $title;
    public Code $code;
    public Count $cycleCount;
    public DiscountType $discountType;
    public Amount $amount;

    public null|false|BillingCycle $billingCycle = false;
    public ?Count $redemptionLimit = null;
    public null|false|Id|PlanEntity $plan = false;
    public ?Status $status = null;
    public null|false|DateTimeInterface $startsAt = false;
    public null|false|DateTimeInterface $expiresAt = false;

    public function __construct(
        string $title,
        string $code,
        int $amount,
        ?string $discountType = null,
        ?int $cycleCount = null,
    ) {
        $this->title = new Title($title);
        $this->code = new Code($code);
        $this->cycleCount = new Count($cycleCount);
        $this->discountType = $discountType
            ? DiscountType::from($discountType)
            : DiscountType::PERCENTAGE;
        $this->amount = new Amount($amount);
    }

    public function setBillingCycle(?string $billingCycle): self
    {
        $this->billingCycle = is_null($billingCycle)
            ? null
            : BillingCycle::from($billingCycle);

        return $this;
    }

    public function setRedemptionLimit(?int $redemptionLimit): self
    {
        $this->redemptionLimit = new Count($redemptionLimit);
        return $this;
    }

    public function setPlan(null|string|Id|PlanEntity $plan): self
    {
        $this->plan = is_string($plan)
            ? new Id($plan)
            : $plan;

        return $this;
    }

    public function setStatus(int $status): self
    {
        $this->status = Status::from($status);
        return $this;
    }

    public function setStartsAt(null|string|DateTimeInterface $startsAt): self
    {
        $this->startsAt = is_string($startsAt)
            ? new DateTime(is_numeric($startsAt) ? '@' . $startsAt : $startsAt)
            : $startsAt;

        return $this;
    }

    public function setExpiresAt(null|string|DateTimeInterface $expiresAt): self
    {
        $this->expiresAt = is_string($expiresAt)
            ? new DateTime(is_numeric($expiresAt) ? '@' . $expiresAt : $expiresAt)
            : $expiresAt;

        return $this;
    }
}
