<?php

declare(strict_types=1);

namespace Billing\Domain\Entities;

use Billing\Domain\Exceptions\NotDueException;
use Billing\Domain\Exceptions\NotSubscriptionPlanException;
use Billing\Domain\ValueObjects\CreditCount;
use Billing\Domain\ValueObjects\ExternalId;
use Billing\Domain\ValueObjects\PaymentGateway;
use Billing\Domain\ValueObjects\SubscriptionStatus;
use Billing\Domain\ValueObjects\TrialPeriodDays;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Shared\Domain\ValueObjects\Id;
use Workspace\Domain\Entities\WorkspaceEntity;

#[ORM\Entity]
#[ORM\Table(name: 'subscription')]
#[ORM\UniqueConstraint(columns: ['payment_gateway', 'external_id'])]
#[ORM\HasLifecycleCallbacks]
class SubscriptionEntity
{
    /**  A unique numeric identifier of the entity. */
    #[ORM\Embedded(class: Id::class, columnPrefix: false)]
    private Id $id;

    #[ORM\Embedded(class: TrialPeriodDays::class, columnPrefix: false)]
    private TrialPeriodDays $trialPeriodDays;

    #[ORM\Embedded(class: CreditCount::class, columnPrefix: 'usage_')]
    private CreditCount $usageCount;

    #[ORM\Embedded(class: PaymentGateway::class, columnPrefix: false)]
    private PaymentGateway $paymentGateway;

    #[ORM\Embedded(class: ExternalId::class, columnPrefix: false)]
    private ExternalId $externalId;

    /** Creation date and time of the entity */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'created_at')]
    private DateTimeInterface $createdAt;

    /** The date and time when the entity was last modified. */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, name: 'updated_at', nullable: true)]
    private ?DateTimeInterface $updatedAt = null;

    /** The date and time when the cancellation request received */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, name: 'canceled_at', nullable: true)]
    private ?DateTimeInterface $canceledAt = null;

    /** Cancel subscription at this datetime */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, name: 'cancel_at', nullable: true)]
    private ?DateTimeInterface $cancelAt = null;

    /** Datetime when the subscription is actually cancelled/ended */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, name: 'ended_at', nullable: true)]
    private ?DateTimeInterface $endedAt = null;

    /** 
     * The date and time to reset the credit usage next time. 
     * For one time payments, this value is always null.
     * 
     * For recurring payments, this value is set to the next current 
     * reset_at plus 30 days. 
     * 
     * First reset date is set at the time of the activation to the 
     * trials_period_days later (if trial_days > 0), otherwise it is set to the 
     * current date plus 30 days .
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, name: 'renew_at', nullable: true)]
    private ?DateTimeInterface $renewAt = null;

    #[ORM\OneToOne(targetEntity: OrderEntity::class, inversedBy: 'subscription')]
    private ?OrderEntity $order = null;

    #[ORM\ManyToOne(targetEntity: PlanSnapshotEntity::class)]
    #[ORM\JoinColumn(nullable: false)]
    private PlanSnapshotEntity $plan;

    #[ORM\ManyToOne(targetEntity: WorkspaceEntity::class, inversedBy: 'subscriptions')]
    #[ORM\JoinColumn(nullable: false)]
    private WorkspaceEntity $workspace;

    public static function createFromOrder(OrderEntity $order): self
    {
        $subs = new self(
            $order->getWorkspace(),
            $order->getPlan(),
            $order->getTrialPeriodDays()
        );

        $subs->order = $order;
        $subs->externalId = $order->getExternalId();
        $subs->paymentGateway = $order->getPaymentGateway();

        if ($order->isPaid()) {
            $subs->cancelAt = null;
            $subs->canceledAt = null;

            $renewIn = $subs->trialPeriodDays->value > 0
                ? $subs->trialPeriodDays->value : 30;
            $subs->renewAt = new DateTime(
                $subs->createdAt->format('Y-m-d H:i:s') . " +{$renewIn} days"
            );
        }

        $order->setSubscription($subs);

        return $subs;
    }

    /**
     * @throws NotSubscriptionPlanException
     */
    public function __construct(
        WorkspaceEntity $workspace,
        PlanSnapshotEntity $plan,
        ?TrialPeriodDays $trialPeriodDays = null,
    ) {
        if (!$plan->getBillingCycle()->isRenewable()) {
            throw new NotSubscriptionPlanException($plan);
        }

        $this->id = new Id();
        $this->trialPeriodDays = $trialPeriodDays ?: new TrialPeriodDays(0);
        $this->usageCount = new CreditCount(0);
        $this->paymentGateway = new PaymentGateway();
        $this->externalId = new ExternalId();
        $this->createdAt = new DateTimeImmutable();
        $this->plan = $plan;

        $renewIn = $this->trialPeriodDays->value > 0 ? $this->trialPeriodDays->value : 30;
        $this->renewAt = new DateTime(
            $this->createdAt->format('Y-m-d H:i:s') . " +{$renewIn} days"
        );

        if ($this->trialPeriodDays->value > 0) {
            $this->cancel();
        }

        $this->workspace = $workspace;
        $workspace->addSubscription($this);
    }

    public function getId(): Id
    {
        return $this->id;
    }

    public function getTrialPeriodDays(): TrialPeriodDays
    {
        return $this->trialPeriodDays;
    }

    public function getUsageCount(): CreditCount
    {
        return $this->usageCount;
    }

    public function getPaymentGateway(): PaymentGateway
    {
        return $this->paymentGateway;
    }

    public function getExternalId(): ExternalId
    {
        return $this->externalId;
    }

    /** @return DateTimeInterface  */
    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    /** @return null|DateTimeInterface  */
    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function getCanceledAt(): ?DateTimeInterface
    {
        return $this->canceledAt;
    }

    public function getCancelAt(): ?DateTimeInterface
    {
        return $this->cancelAt;
    }

    public function getEndedAt(): ?DateTimeInterface
    {
        return $this->endedAt;
    }

    public function getRenewAt(): ?DateTimeInterface
    {
        return $this->renewAt;
    }

    public function getOrder(): ?OrderEntity
    {
        return $this->order;
    }

    public function getPlan(): PlanSnapshotEntity
    {
        return $this->plan;
    }

    /**
     * Get remaining credit
     *
     * @return CreditCount
     */
    public function getCredit(): CreditCount
    {
        $plan = $this->plan;

        if ($plan->getCreditCount()->value === null) {
            // Unlimited token credit
            return new CreditCount();
        }

        if ($this->usageCount->value === null) {
            // No usage yet
            return $plan->getCreditCount();
        }

        $credit = (float) $plan->getCreditCount()->value - (float) $this->usageCount->value;
        return new CreditCount($credit > 0 ? $credit : 0);
    }

    /**
     * Deduct credit from the subscription
     *
     * @param CreditCount $count
     * @return CreditCount The remaining credit after deduction
     */
    public function deductCredit(CreditCount $count): CreditCount
    {
        $credit = $this->getCredit();

        if ((float) $credit->value <= 0) {
            return $count;
        }

        if (
            $credit->value === null
            || (float)$credit->value > (float)$count->value
        ) {
            $this->usageCount = new CreditCount(
                (float) $this->usageCount->value + (float) $count->value
            );

            return new CreditCount(0);
        }

        $this->usageCount = new CreditCount(
            (float) $this->usageCount->value + (float) $credit->value
        );

        return new CreditCount((float) $count->value - (float) $credit->value);
    }

    public function getStatus(): SubscriptionStatus
    {
        if ($this->endedAt) {
            return SubscriptionStatus::ENDED;
        }

        if ($this->canceledAt) {
            return SubscriptionStatus::CANCELED;
        }

        if ($this->renewAt) {
            if (
                $this->trialPeriodDays->value > 0
                && $this->createdAt > new DateTime('-' . $this->trialPeriodDays->value . ' days')
            ) {
                return SubscriptionStatus::TRIALING;
            }

            return SubscriptionStatus::ACTIVE;
        }

        return SubscriptionStatus::UNKNOWN;
    }

    public function cancel(): void
    {
        if ($this->canceledAt) {
            return;
        }

        $this->canceledAt = new DateTime();

        if ($this->renewAt) {
            $this->cancelAt = $this->renewAt;
            $this->renewAt = null;

            return;
        }

        $this->cancelAt = new DateTime();
    }

    public function end(): void
    {
        $this->cancel();
        $this->endedAt = new DateTime();
    }

    public function isExpired(): bool
    {
        if (!$this->cancelAt) {
            return false;
        }

        return $this->cancelAt < new DateTime();
    }

    public function getWorkspace(): WorkspaceEntity
    {
        return $this->workspace;
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new DateTime();
    }

    /** @return SubscriptionEntity  */
    public function renew(): self
    {
        if (
            !$this->renewAt
            || $this->renewAt > new DateTime()
        ) {
            throw new NotDueException($this);
        }

        $ws = $this->getWorkspace();
        $snapshot = $this->getPlan();

        // Reset usage count
        $this->usageCount = new CreditCount(0);

        // Close debt if any
        $balance = $ws->getCreditCount();
        if ($balance->value !== null && (float) $balance->value < 0) {
            $max = $snapshot->getCreditCount();
            $debt = abs((float) $balance->value);

            if ($debt <= (float) $max->value) {
                // We have enough credits to close the debt
                $this->usageCount = new CreditCount($debt);
            } else {
                // We don't have enough credits to close the debt
                // So we use the max credit
                $this->usageCount = $max;
            }

            // Deduct from debt
            $ws->addCredits($this->usageCount);
        }

        // Set next reset date
        $this->renewAt = new DateTime(
            $this->renewAt->format('Y-m-d H:i:s') . " +30 days"
        );

        return $this;
    }
}
