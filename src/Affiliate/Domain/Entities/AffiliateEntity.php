<?php

declare(strict_types=1);

namespace Affiliate\Domain\Entities;

use Affiliate\Domain\Exceptions\InsufficientBalanceException;
use Affiliate\Domain\ValueObjects\Amount;
use Affiliate\Domain\ValueObjects\BankRequisites;
use Affiliate\Domain\ValueObjects\Code;
use Affiliate\Domain\ValueObjects\Count;
use Affiliate\Domain\ValueObjects\PayoutMethod;
use Affiliate\Domain\ValueObjects\PayPalEmail;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use User\Domain\Entities\UserEntity;
use Shared\Domain\ValueObjects\Id;

#[ORM\Entity]
#[ORM\Table(name: 'affiliate')]
class AffiliateEntity
{
    /** A unique numeric identifier of the entity. */
    #[ORM\Embedded(class: Id::class, columnPrefix: false)]
    private Id $id;

    #[ORM\Embedded(class: PayPalEmail::class, columnPrefix: false)]
    private PayPalEmail $payPalEmail;

    #[ORM\Embedded(class: BankRequisites::class, columnPrefix: false)]
    private BankRequisites $bankRequisites;

    #[ORM\Embedded(class: Code::class, columnPrefix: false)]
    private Code $code;

    #[ORM\Embedded(class: Count::class, columnPrefix: 'click_')]
    private Count $clickCount;

    #[ORM\Embedded(class: Count::class, columnPrefix: 'referral_')]
    private Count $referralCount;

    #[ORM\Embedded(class: Amount::class, columnPrefix: 'balance_')]
    private Amount $balance;

    #[ORM\Embedded(class: Amount::class, columnPrefix: 'pending_')]
    private Amount $pending;

    #[ORM\Embedded(class: Amount::class, columnPrefix: 'withdrawn_')]
    private Amount $withdrawn;

    #[ORM\OneToOne(targetEntity: UserEntity::class, inversedBy: 'affiliate')]
    #[ORM\JoinColumn(nullable: false)]
    private UserEntity $user;

    #[ORM\OneToMany(targetEntity: PayoutEntity::class, mappedBy: 'affiliate', cascade: ['persist', 'remove'])]
    private Collection $payouts;

    #[ORM\Column(type: Types::STRING, enumType: PayoutMethod::class, name: 'payout_method', nullable: true)]
    private ?PayoutMethod $payoutMethod = null;

    public function __construct(
        UserEntity $user,
    ) {
        $this->id = new Id();
        $this->user = $user;
        $this->payPalEmail = new PayPalEmail();
        $this->bankRequisites = new BankRequisites();
        $this->code = new Code();
        $this->clickCount = new Count(0);
        $this->referralCount = new Count(0);
        $this->balance = new Amount(0);
        $this->pending = new Amount(0);
        $this->withdrawn = new Amount(0);

        $this->payouts = new ArrayCollection();
    }

    public function getId(): Id
    {
        return $this->id;
    }

    public function getPayPalEmail(): PayPalEmail
    {
        return $this->payPalEmail;
    }

    public function setPayPalEmail(PayPalEmail $payPalEmail): self
    {
        $this->payPalEmail = $payPalEmail;
        return $this;
    }

    public function getBankRequisites(): BankRequisites
    {
        return $this->bankRequisites;
    }

    public function setBankRequisites(BankRequisites $bankRequisites): self
    {
        $this->bankRequisites = $bankRequisites;
        return $this;
    }

    public function getCode(): Code
    {
        return $this->code;
    }

    public function getClickCount(): Count
    {
        return $this->clickCount;
    }

    public function getReferralCount(): Count
    {
        return $this->referralCount;
    }

    public function getBalance(): Amount
    {
        return $this->balance;
    }

    public function getPending(): Amount
    {
        return $this->pending;
    }

    public function getWithdrawn(): Amount
    {
        return $this->withdrawn;
    }

    public function getUser(): UserEntity
    {
        return $this->user;
    }

    public function getPayoutMethod(): ?PayoutMethod
    {
        return $this->payoutMethod;
    }

    public function setPayoutMethod(PayoutMethod $payoutMethod): self
    {
        $this->payoutMethod = $payoutMethod;
        return $this;
    }

    /**
     * Increment the click count for this affiliate.
     */
    public function click(): void
    {
        $this->clickCount = new Count($this->clickCount->value + 1);
    }

    /**
     * Increment the referral count for this affiliate.
     */
    public function referral(): void
    {
        $this->referralCount = new Count($this->referralCount->value + 1);
    }

    /**
     * Add a conversion amount to the affiliate's balance.
     *
     * @param Amount $amount The amount to be added to the affiliate's balance.
     */
    public function conversion(Amount $amount): void
    {
        $this->balance = new Amount($this->balance->value + $amount->value);
    }

    public function payout(): PayoutEntity
    {
        if ($this->balance->value <= 0) {
            throw new InsufficientBalanceException();
        }

        // Create payout with amount of balance
        $payout = new PayoutEntity($this, $this->balance);
        $this->payouts->add($payout);

        // Update pending balance
        $this->pending = new Amount($this->pending->value + $this->balance->value);

        // Update balance
        $this->balance = new Amount(0);

        return $payout;
    }

    public function approvePayout(PayoutEntity $payout): void
    {
        // Check if the payout belongs to this affiliate
        if (!$this->payouts->contains($payout)) {
            throw new InvalidArgumentException('This payout does not belong to this affiliate.');
        }

        // Move the amount from pending to withdrawn
        $payoutAmount = $payout->getAmount();
        $this->pending = new Amount($this->pending->value - $payoutAmount->value);
        $this->withdrawn = new Amount($this->withdrawn->value + $payoutAmount->value);
    }

    public function rejectPayout(PayoutEntity $payout): void
    {
        // Check if the payout belongs to this affiliate
        if (!$this->payouts->contains($payout)) {
            throw new InvalidArgumentException('This payout does not belong to this affiliate.');
        }

        // Move the amount from pending to balance
        $payoutAmount = $payout->getAmount();
        $this->pending = new Amount($this->pending->value - $payoutAmount->value);
        $this->balance = new Amount($this->balance->value + $payoutAmount->value);
    }

    public function addEarnings(Amount $amount): void
    {
        $this->balance = new Amount($this->balance->value + $amount->value);
    }
}
