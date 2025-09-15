<?php

declare(strict_types=1);

namespace Billing\Domain\Entities;

use Billing\Domain\Exceptions\CouponVoidType;
use Billing\Domain\Exceptions\InvalidCouponException;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Billing\Domain\ValueObjects\BillingCycle;
use Billing\Domain\ValueObjects\CreditCount;
use Billing\Domain\ValueObjects\Description;
use Billing\Domain\ValueObjects\DiscountType;
use Billing\Domain\ValueObjects\FeatureList;
use Billing\Domain\ValueObjects\Icon;
use Billing\Domain\ValueObjects\IsFeatured;
use Billing\Domain\ValueObjects\MemberCap;
use Billing\Domain\ValueObjects\PlanConfig;
use Billing\Domain\ValueObjects\Price;
use Billing\Domain\ValueObjects\Status;
use Billing\Domain\ValueObjects\Superiority;
use Billing\Domain\ValueObjects\Title;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Shared\Domain\ValueObjects\Id;

#[ORM\Entity]
#[ORM\Table(name: 'plan')]
#[ORM\HasLifecycleCallbacks]
class PlanEntity extends AbstractPlanSuperclass
{
    #[ORM\Column(type: Types::SMALLINT, enumType: Status::class, name: 'status')]
    private Status $status;

    #[ORM\Embedded(class: Superiority::class, columnPrefix: false)]
    private Superiority $superiority;

    #[ORM\Embedded(class: IsFeatured::class, columnPrefix: false)]
    private IsFeatured $isFeatured;

    /** @var Collection<int,PlanSnapshotEntity> */
    #[ORM\OneToMany(targetEntity: PlanSnapshotEntity::class, mappedBy: 'plan', cascade: ['persist'])]
    private Collection $snapshots;

    #[ORM\OneToOne(targetEntity: PlanSnapshotEntity::class)]
    #[ORM\JoinColumn(name: "snapshot_id")]
    private ?PlanSnapshotEntity $snapshot = null;

    private ?PlanSnapshotEntity $pendingSnapshot = null;
    private ?CouponEntity $coupon = null;

    /**
     * @param Title $title 
     * @param Price $price 
     * @param BillingCycle $billingCycle 
     * @return void 
     */
    public function __construct(
        Title $title,
        Price $price,
        BillingCycle $billingCycle
    ) {
        $this->id = new Id();
        $this->title = $title;
        $this->description = new Description();
        $this->icon = new Icon();
        $this->featureList = new FeatureList();
        $this->price = $price;
        $this->billingCycle = $billingCycle;
        $this->creditCount = new CreditCount();
        $this->memberCap = new MemberCap();
        $this->createdAt = new DateTimeImmutable();

        $this->status = Status::ACTIVE;
        $this->superiority = new Superiority();
        $this->isFeatured = new IsFeatured();

        $this->snapshots = new ArrayCollection();
        $this->snapshot = new PlanSnapshotEntity($this);
        $this->pendingSnapshot = new PlanSnapshotEntity($this);
    }

    public function setTitle(Title $title): void
    {
        $this->title = $title;
    }

    public function setDescription(Description $description): void
    {
        $this->description = $description;
    }

    public function setIcon(Icon $icon): void
    {
        $this->icon = $icon;
    }

    public function setFeatureList(FeatureList $featureList): void
    {
        $this->featureList = $featureList;
    }

    public function setPrice(Price $price): void
    {
        if ($price->value != $this->price->value) {
            $this->price = $price;
            $this->pendingSnapshot = new PlanSnapshotEntity($this);
        }
    }

    public function setBillingCycle(BillingCycle $billingCycle): void
    {
        if ($billingCycle->value != $this->billingCycle->value) {
            $this->billingCycle = $billingCycle;
            $this->pendingSnapshot = new PlanSnapshotEntity($this);
        }
    }

    public function setCreditCount(CreditCount $creditCount): void
    {
        if ($creditCount->value != $this->creditCount->value) {
            $this->creditCount = $creditCount;
            $this->pendingSnapshot = new PlanSnapshotEntity($this);
        }
    }

    public function setMemberCap(MemberCap $memberCap): void
    {
        if ($memberCap->value !== $this->memberCap->value) {
            $this->memberCap = $memberCap;
            $this->pendingSnapshot = new PlanSnapshotEntity($this);
        }
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function setStatus(Status $status): void
    {
        $this->status = $status;
    }

    public function getSuperiority(): Superiority
    {
        return $this->superiority;
    }

    public function setSuperiority(Superiority $superiority): void
    {
        $this->superiority = $superiority;
    }

    public function getIsFeatured(): IsFeatured
    {
        return $this->isFeatured;
    }

    public function setIsFeatured(IsFeatured $isFeatured): void
    {
        $this->isFeatured = $isFeatured;
    }

    public function setConfig(PlanConfig $config): void
    {
        if (json_encode($config) != json_encode($this->config)) {
            $this->config = $config;
            $this->pendingSnapshot = new PlanSnapshotEntity($this);
        }
    }

    #[ORM\PreFlush]
    public function preFlush(): void
    {
        $this->updatedAt = new DateTime();

        if ($this->pendingSnapshot) {
            $this->snapshots->add($this->pendingSnapshot);
            $this->snapshot = $this->pendingSnapshot;
            $this->pendingSnapshot = null;
        }
    }

    public function isActive(): bool
    {
        return $this->getStatus() == Status::ACTIVE;
    }

    public function getSnapshot(): PlanSnapshotEntity
    {
        if ($this->pendingSnapshot) {
            return $this->pendingSnapshot;
        }

        if (!$this->snapshot) {
            $this->pendingSnapshot = new PlanSnapshotEntity($this);
            return $this->pendingSnapshot;
        }

        return $this->snapshot;
    }

    public function resyncSnapshots(): void
    {
        foreach ($this->snapshots as $snapshot) {
            $snapshot->resync();
        }
    }

    /**
     * @throws InvalidCouponException
     */
    public function applyCoupon(CouponEntity $coupon): void
    {
        if ($coupon->getStatus() != Status::ACTIVE) {
            throw new InvalidCouponException(
                coupon: $coupon,
                type: CouponVoidType::INACTIVE
            );
        }

        if ($coupon->isPremature()) {
            throw new InvalidCouponException(
                coupon: $coupon,
                type: CouponVoidType::PREMATURE
            );
        }

        if ($coupon->isExpired()) {
            throw new InvalidCouponException(
                coupon: $coupon,
                type: CouponVoidType::EXPIRED
            );
        }

        if ($coupon->isRedemptionLimitReached()) {
            throw new InvalidCouponException(
                coupon: $coupon,
                type: CouponVoidType::REDEMPTION_LIMIT
            );
        }

        if (!$coupon->isApplicableToPlan($this)) {
            throw new InvalidCouponException(
                coupon: $coupon,
                type: CouponVoidType::PLAN_MISMATCH
            );
        }

        $this->coupon = $coupon;
    }

    public function getCoupon(): ?CouponEntity
    {
        return $this->coupon;
    }

    public function getSalePrice(): ?Price
    {
        if (!$this->coupon) {
            return null;
        }

        $coupon = $this->coupon;
        $salePrice = $coupon->calculateDiscountedAmount(
            $this->price->value
        );

        return new Price($salePrice);
    }

    public function getDiscount(): ?Price
    {
        $coupon = $this->coupon;

        if (!$coupon) {
            return null;
        }

        $type = $coupon->getDiscountType();

        if ($type == DiscountType::PERCENTAGE) {
            $amount = $coupon->getAmount()->value / 100;
            if ($amount > 100) {
                $amount = 100;
            }

            return new Price((int) ($this->price->value * $amount / 100));
        }

        // type is fixed amount
        $amount = $coupon->getAmount()->value;

        if ($amount > $this->price->value) {
            $amount = $this->price->value;
        }

        return new Price((int) $amount);
    }
}
