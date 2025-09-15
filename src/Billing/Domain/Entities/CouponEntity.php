<?php

declare(strict_types=1);

namespace Billing\Domain\Entities;

use Billing\Domain\ValueObjects\BillingCycle;
use Billing\Domain\ValueObjects\Code;
use Billing\Domain\ValueObjects\Count;
use Billing\Domain\ValueObjects\DiscountType;
use Billing\Domain\ValueObjects\Amount;
use Billing\Domain\ValueObjects\Price;
use Billing\Domain\ValueObjects\Status;
use Billing\Domain\ValueObjects\Title;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Shared\Domain\ValueObjects\Id;
use Traversable;

#[ORM\Entity]
#[ORM\Table(name: 'coupon')]
#[ORM\HasLifecycleCallbacks]
class CouponEntity
{
    /** A unique numeric identifier of the entity. */
    #[ORM\Embedded(class: Id::class, columnPrefix: false)]
    private Id $id;

    #[ORM\Column(type: Types::SMALLINT, enumType: Status::class, name: 'status')]
    private Status $status;

    #[ORM\Embedded(class: Title::class, columnPrefix: false)]
    private Title $title;

    #[ORM\Embedded(class: Code::class, columnPrefix: false)]
    private Code $code;

    #[ORM\Embedded(class: Count::class, columnPrefix: 'cycle_')]
    private Count $cycleCount;

    #[ORM\Embedded(class: Count::class, columnPrefix: 'max_redemption_')]
    private Count $redemptionLimit;

    #[ORM\Column(type: Types::STRING, enumType: DiscountType::class, name: 'discount_type')]
    private DiscountType $discountType;

    #[ORM\Embedded(class: Amount::class, columnPrefix: false)]
    private Amount $amount;

    #[ORM\Column(type: Types::STRING, name: 'billing_cycle', enumType: BillingCycle::class, nullable: true)]
    private ?BillingCycle $billingCycle = null;

    /** Creation date and time of the entity */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'created_at')]
    private DateTimeInterface $createdAt;

    /** The date and time when the entity was last modified. */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, name: 'updated_at', nullable: true)]
    private ?DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, name: 'starts_at', nullable: true)]
    private ?DateTimeInterface $startsAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, name: 'expires_at', nullable: true)]
    private ?DateTimeInterface $expiresAt = null;

    #[ORM\ManyToOne(targetEntity: PlanEntity::class)]
    #[ORM\JoinColumn(name: "plan_id", nullable: true, onDelete: 'SET NULL')]
    private ?PlanEntity $plan = null;

    #[ORM\OneToMany(targetEntity: OrderEntity::class, mappedBy: 'coupon')]
    private Collection $orders;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'deleted_at', nullable: true)]
    private ?DateTimeInterface $deletedAt = null;

    public function __construct(
        Title $title,
        Code $code,
        DiscountType $discountType,
        Amount $amount,
        Count $cycleCount,
    ) {
        $this->id = new Id();
        $this->status = Status::ACTIVE;
        $this->title = $title;
        $this->code = $code;
        $this->cycleCount = $cycleCount;
        $this->redemptionLimit = new Count();
        $this->discountType = $discountType;
        $this->amount = $amount;
        $this->createdAt = new DateTimeImmutable();
        $this->orders = new ArrayCollection();
    }

    public function getId(): Id
    {
        return $this->id;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function setStatus(Status $status): void
    {
        $this->status = $status;
    }

    public function getTitle(): Title
    {
        return $this->title;
    }

    public function setTitle(Title $title): void
    {
        $this->title = $title;
    }

    public function getCode(): Code
    {
        return $this->code;
    }

    public function getCycleCount(): Count
    {
        return $this->cycleCount;
    }

    public function getRedemptionLimit(): Count
    {
        return $this->redemptionLimit;
    }

    public function setRedemptionLimit(Count $limit): void
    {
        $this->redemptionLimit = $limit;
    }

    public function getDiscountType(): DiscountType
    {
        return $this->discountType;
    }

    public function getAmount(): Amount
    {
        return $this->amount;
    }

    public function getBillingCycle(): ?BillingCycle
    {
        return $this->billingCycle;
    }

    public function setBillingCycle(?BillingCycle $billingCycle): void
    {
        $this->billingCycle = $billingCycle;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function getStartsAt(): ?DateTimeInterface
    {
        return $this->startsAt;
    }

    public function setStartsAt(?DateTimeInterface $startsAt): void
    {
        if ($startsAt instanceof DateTimeImmutable) {
            // Convert to mutable DateTime
            $startsAt = DateTime::createFromImmutable($startsAt);
        }

        $this->startsAt = $startsAt;
    }

    public function getExpiresAt(): ?DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function isPremature(): bool
    {
        return $this->startsAt && $this->startsAt > new DateTime();
    }

    public function setExpiresAt(?DateTimeInterface $expiresAt): void
    {
        if ($expiresAt instanceof DateTimeImmutable) {
            // Convert to mutable DateTime
            $expiresAt = DateTime::createFromImmutable($expiresAt);
        }

        $this->expiresAt = $expiresAt;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt && $this->expiresAt < new DateTime();
    }

    public function getDeletedAt(): ?DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function getPlan(): ?PlanEntity
    {
        return $this->plan;
    }

    public function setPlan(?PlanEntity $plan): void
    {
        $this->plan = $plan;
    }

    public function getRedemptionCount(): int
    {
        return $this->orders->count();
    }

    public function getRedemptions(): Traversable
    {
        yield from $this->orders;
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new DateTime();
    }

    /** @internal Don't use this method directly. */
    public function markAsDeleted(): void
    {
        $this->deletedAt = new DateTimeImmutable();
    }

    public function isRedemptionLimitReached(): bool
    {
        $limit = $this->redemptionLimit->value;

        if (is_null($limit)) {
            return false;
        }

        $count = $this->getRedemptionCount();
        return $count >= $limit;
    }

    public function isApplicableToPlan(PlanEntity $plan): bool
    {
        if ($this->plan) {
            return $this->plan->getId()->equals($plan->getId());
        }

        if ($this->billingCycle) {
            return $this->billingCycle->value == $plan->getBillingCycle()->value;
        }

        return true;
    }

    /**
     * A helper method to calculate the discounted amount for a given amount.
     * 
     * @template T of int|Price
     * @param T $amount The amount to calculate the discounted amount for.
     * @return T The discounted amount.
     */
    public function calculateDiscountedAmount(int|Price $amount): int|Price
    {
        $isInt = is_int($amount);

        $type = $this->discountType;
        if ($amount instanceof Price) {
            $amount = $amount->value;
        }

        if ($type == DiscountType::PERCENTAGE) {
            // Amount is stored as integer with 2 decimal places (like monetary values)
            // e.g., 2500 = 25.00%, 1000 = 10.00%, 500 = 5.00%
            $discount = $this->amount->value / 100;
            if ($discount > 100) {
                $discount = 100;
            }

            $calculated = (int) round($amount - ($amount * $discount / 100));
            return $isInt ? $calculated : new Price($calculated);
        }

        // type is fixed amount
        $discount = $this->amount->value;

        $calculated = $amount - $discount;

        if ($calculated < 0) {
            $calculated = 0;
        }

        return $isInt ? $calculated : new Price($calculated);
    }
}
