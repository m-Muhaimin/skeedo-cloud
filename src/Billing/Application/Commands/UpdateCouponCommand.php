<?php

declare(strict_types=1);

namespace Billing\Application\Commands;

use Billing\Application\CommandHandlers\UpdateCouponCommandHandler;
use Billing\Domain\Entities\CouponEntity;
use Billing\Domain\Entities\PlanEntity;
use Billing\Domain\ValueObjects\BillingCycle;
use Billing\Domain\ValueObjects\Code;
use Billing\Domain\ValueObjects\Count;
use Billing\Domain\ValueObjects\Status;
use Billing\Domain\ValueObjects\Title;
use DateTime;
use DateTimeInterface;
use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;
use Throwable;

#[Handler(UpdateCouponCommandHandler::class)]
class UpdateCouponCommand
{
    public Id|Code|CouponEntity $id;

    public ?Title $title = null;
    public null|false|BillingCycle $billingCycle = false;
    public ?Count $redemptionLimit = null;
    public null|false|Id|PlanEntity $plan = false;
    public ?Status $status = null;
    public null|false|DateTimeInterface $startsAt = false;
    public null|false|DateTimeInterface $expiresAt = false;

    public function __construct(Id|Code|CouponEntity|string $id)
    {
        if (is_string($id)) {
            try {
                $this->id = new Id($id);
            } catch (Throwable $e) {
                $this->id = new Code($id);
            }
        } else {
            $this->id = $id;
        }
    }

    public function setTitle(string $title): self
    {
        $this->title = new Title($title);
        return $this;
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
        $this->redemptionLimit =  new Count($redemptionLimit);
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
