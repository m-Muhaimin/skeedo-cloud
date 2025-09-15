<?php

declare(strict_types=1);

namespace Billing\Domain\Entities;

use Billing\Domain\ValueObjects\BillingCycle;
use Billing\Domain\ValueObjects\CreditCount;
use Billing\Domain\ValueObjects\Description;
use Billing\Domain\ValueObjects\FeatureList;
use Billing\Domain\ValueObjects\Icon;
use Billing\Domain\ValueObjects\MemberCap;
use Billing\Domain\ValueObjects\PlanConfig;
use Billing\Domain\ValueObjects\Price;
use Billing\Domain\ValueObjects\Title;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Shared\Domain\ValueObjects\Id;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractPlanSuperclass
{
    /**  A unique numeric identifier of the entity. */
    #[ORM\Embedded(class: Id::class, columnPrefix: false)]
    protected Id $id;

    #[ORM\Embedded(class: Title::class, columnPrefix: false)]
    protected Title $title;

    #[ORM\Embedded(class: Description::class, columnPrefix: false)]
    protected Description $description;

    #[ORM\Embedded(class: Icon::class, columnPrefix: false)]
    protected Icon $icon;

    #[ORM\Embedded(class: FeatureList::class, columnPrefix: false)]
    protected FeatureList $featureList;

    #[ORM\Embedded(class: Price::class, columnPrefix: false)]
    protected Price $price;

    #[ORM\Column(type: Types::STRING, name: 'billing_cycle', enumType: BillingCycle::class, nullable: true)]
    protected BillingCycle $billingCycle;

    #[ORM\Embedded(class: CreditCount::class, columnPrefix: 'credit_')]
    protected CreditCount $creditCount;

    #[ORM\Embedded(class: MemberCap::class, columnPrefix: false)]
    protected MemberCap $memberCap;

    /** Creation date and time of the entity */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'created_at')]
    protected DateTimeInterface $createdAt;

    /** The date and time when the entity was last modified. */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, name: 'updated_at', nullable: true)]
    protected ?DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::JSON, name: "config", nullable: true)]
    protected null|array|PlanConfig $config = null;

    public function getId(): Id
    {
        return $this->id;
    }

    public function getTitle(): Title
    {
        return $this->title;
    }

    public function getDescription(): Description
    {
        return $this->description;
    }

    public function getIcon(): Icon
    {
        return $this->icon;
    }

    public function getFeatureList(): FeatureList
    {
        return $this->featureList;
    }

    public function getPrice(): Price
    {
        return $this->price;
    }

    public function getBillingCycle(): BillingCycle
    {
        return $this->billingCycle;
    }

    public function getCreditCount(): CreditCount
    {
        return $this->creditCount;
    }

    public function getMemberCap(): MemberCap
    {
        return $this->memberCap;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function getConfig(): PlanConfig
    {
        if (!$this->config instanceof PlanConfig) {
            $this->config = new PlanConfig($this->config);
        }

        return $this->config;
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new DateTime();
    }
}
