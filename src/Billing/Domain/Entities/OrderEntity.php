<?php

declare(strict_types=1);

namespace Billing\Domain\Entities;

use Billing\Domain\Exceptions\AlreadyFulfilledException;
use Billing\Domain\Exceptions\AlreadyPaidException;
use Billing\Domain\Exceptions\InvalidOrderStateException;
use Billing\Domain\ValueObjects\ExternalId;
use Billing\Domain\ValueObjects\OrderStatus;
use Billing\Domain\ValueObjects\PaymentGateway;
use Billing\Domain\ValueObjects\Price;
use Billing\Domain\ValueObjects\TrialPeriodDays;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use DomainException;
use Shared\Domain\ValueObjects\CurrencyCode;
use Shared\Domain\ValueObjects\Id;
use Workspace\Domain\Entities\WorkspaceEntity;

#[ORM\Entity]
#[ORM\Table(name: '`order`')]
#[ORM\Index(columns: ['status'])]
#[ORM\HasLifecycleCallbacks]
class OrderEntity
{
    /** A unique numeric identifier of the entity. */
    #[ORM\Embedded(class: Id::class, columnPrefix: false)]
    private Id $id;

    #[ORM\Column(name: "currency_code", type: Types::STRING, length: 3, enumType: CurrencyCode::class)]
    private CurrencyCode $currencyCode;

    #[ORM\Embedded(class: TrialPeriodDays::class, columnPrefix: false)]
    private TrialPeriodDays $trialPeriodDays;

    #[ORM\Embedded(class: PaymentGateway::class, columnPrefix: false)]
    private PaymentGateway $paymentGateway;

    #[ORM\Embedded(class: ExternalId::class, columnPrefix: false)]
    private ExternalId $externalId;

    #[ORM\Column(type: Types::STRING, enumType: OrderStatus::class, name: 'status', length: 20)]
    private OrderStatus $status;

    /** Creation date and time of the entity */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'created_at')]
    private DateTimeInterface $createdAt;

    /** The date and time when the entity was last modified. */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, name: 'updated_at', nullable: true)]
    private ?DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: WorkspaceEntity::class, inversedBy: 'orders')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private WorkspaceEntity $workspace;

    #[ORM\ManyToOne(targetEntity: PlanSnapshotEntity::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: "plan_snapshot_id", nullable: false)]
    private PlanSnapshotEntity $plan;

    #[ORM\OneToOne(targetEntity: SubscriptionEntity::class, mappedBy: 'order')]
    private ?SubscriptionEntity $subscription = null;

    #[ORM\ManyToOne(targetEntity: CouponEntity::class, inversedBy: 'orders')]
    private ?CouponEntity $coupon = null;

    public function __construct(
        WorkspaceEntity $workspace,
        PlanEntity|PlanSnapshotEntity $plan,
        CurrencyCode $currencyCode,
        ?TrialPeriodDays $trialPeriodDays = null,
        ?CouponEntity $coupon = null
    ) {
        if ($plan->getPrice()->value == 0 && $plan->getBillingCycle()->isRecurring()) {
            $sub = $workspace->getSubscription();

            if ($sub && $sub->getPlan()->getPrice()->value == 0) {
                throw new DomainException('Workspace already has a free subscription');
            }

            if (!$workspace->isEligibleForFreePlan()) {
                throw new DomainException('Workspace is not eligible for a free plan');
            }
        }

        if ($coupon) {
            // This validates the coupon and applies it to the plan
            $plan->applyCoupon($coupon);
        }

        $this->id = new Id();
        $this->currencyCode = $currencyCode;

        $this->trialPeriodDays = $trialPeriodDays
            && $workspace->isEligibleForTrial()
            && $plan->getPrice()->value > 0
            && $plan->getBillingCycle()->isRecurring() ? $trialPeriodDays
            : new TrialPeriodDays();

        $this->paymentGateway = new PaymentGateway();
        $this->externalId = new ExternalId();
        $this->status = OrderStatus::DRAFT;

        $this->createdAt = new DateTimeImmutable();
        $this->workspace = $workspace;
        $this->plan = $plan instanceof PlanEntity ? $plan->getSnapshot() : $plan;
        $this->coupon = $coupon;
    }

    public function getId(): Id
    {
        return $this->id;
    }

    public function getCurrencyCode(): CurrencyCode
    {
        return $this->currencyCode;
    }

    public function getTrialPeriodDays(): TrialPeriodDays
    {
        return $this->trialPeriodDays;
    }

    public function getPaymentGateway(): PaymentGateway
    {
        return $this->paymentGateway;
    }

    public function getExternalId(): ExternalId
    {
        return $this->externalId;
    }

    public function isPaid(): bool
    {
        return in_array(
            $this->status,
            [
                OrderStatus::PROCESSING,
                OrderStatus::COMPLETED,
            ]
        );
    }

    public function isFulfilled(): bool
    {
        return $this->status === OrderStatus::COMPLETED;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new DateTime();
    }

    public function getWorkspace(): WorkspaceEntity
    {
        return $this->workspace;
    }

    public function getPlan(): PlanSnapshotEntity
    {
        return $this->plan;
    }

    public function getSubscription(): ?SubscriptionEntity
    {
        return $this->subscription;
    }

    public function getCoupon(): ?CouponEntity
    {
        return $this->coupon;
    }

    public function getStatus(): OrderStatus
    {
        return $this->status;
    }

    public function getSubtotal(): Price
    {
        return $this->plan->getPrice();
    }

    public function getDiscount(): Price
    {
        $subtotal = $this->getSubtotal()->value;

        $coupon = $this->coupon;
        $discounted = $coupon ? $coupon->calculateDiscountedAmount($subtotal) : $subtotal;
        return new Price($subtotal - $discounted);
    }

    public function getTotalPrice(): Price
    {
        $coupon = $this->coupon;
        if (!$coupon) {
            return $this->plan->getPrice();
        }

        $cycle = $this->plan->getBillingCycle();
        if ($cycle->isRecurring()) {
            return $this->plan->getPrice();
        }

        $salePrice = $coupon->calculateDiscountedAmount(
            $this->plan->getPrice()->value
        );

        return new Price($salePrice);
    }

    /**
     * @internal
     */
    public function setSubscription(SubscriptionEntity $subscription): void
    {
        $id = $subscription->getOrder()->getId();

        if ($this->subscription) {
            throw new DomainException('Subscription already set');
        }

        if (!$id->equals($this->id)) {
            throw new DomainException('Subscription order ID does not match');
        }

        $this->subscription = $subscription;
    }

    /**
     * Moves the order to the pending state
     * 
     * @throws InvalidOrderStateException
     */
    public function initiatePayment(
        ?PaymentGateway $paymentGateway = null,
        ?ExternalId $externalId = null,
    ): void {
        $allowed = [OrderStatus::DRAFT, OrderStatus::PENDING];
        if (!in_array($this->status, $allowed)) {
            throw new InvalidOrderStateException($this, OrderStatus::PENDING);
        }

        $this->paymentGateway = $paymentGateway ?: new PaymentGateway();
        $this->externalId = $externalId ?: new ExternalId();
        $this->status = OrderStatus::PENDING;
    }

    /**
     * Moves the order to the processing state
     * @throws InvalidOrderStateException
     */
    public function pay(
        ?PaymentGateway $paymentGateway = null,
        ?ExternalId $externalId = null,
    ): void {
        if ($this->isFulfilled()) {
            throw new AlreadyFulfilledException($this);
        }

        if ($this->isPaid()) {
            throw new AlreadyPaidException($this);
        }

        $allowed = [OrderStatus::DRAFT, OrderStatus::PENDING];
        if (!in_array($this->status, $allowed)) {
            throw new InvalidOrderStateException($this, OrderStatus::PROCESSING);
        }

        $this->paymentGateway = $paymentGateway ?: new PaymentGateway();
        $this->externalId = $externalId ?: new ExternalId();
        $this->status = OrderStatus::PROCESSING;
    }

    /**
     * Moves the order to the completed state
     * 
     * @throws InvalidOrderStateException
     */
    public function fulfill(): void
    {
        $allowed = [OrderStatus::DRAFT, OrderStatus::PENDING, OrderStatus::PROCESSING];
        if (!in_array($this->status, $allowed)) {
            throw new InvalidOrderStateException($this, OrderStatus::COMPLETED);
        }

        $this->status = OrderStatus::COMPLETED;
    }

    /**
     * Moves the order to the cancelled state
     * 
     * @throws InvalidOrderStateException
     */
    public function cancel(): void
    {
        $allowed = [OrderStatus::DRAFT, OrderStatus::PENDING];
        if (!in_array($this->status, $allowed)) {
            throw new InvalidOrderStateException($this, OrderStatus::CANCELLED);
        }

        $this->status = OrderStatus::CANCELLED;
    }
}
