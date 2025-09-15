<?php

declare(strict_types=1);

namespace Billing\Domain\Entities;

use Billing\Domain\ValueObjects\PlanConfig;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Shared\Domain\ValueObjects\Id;

#[ORM\Entity]
#[ORM\Table(name: 'plan_snapshot')]
class PlanSnapshotEntity extends AbstractPlanSuperclass
{
    #[ORM\ManyToOne(targetEntity: PlanEntity::class, inversedBy: 'snapshots')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?PlanEntity $plan = null;

    public function __construct(
        PlanEntity $plan
    ) {
        $this->id = new Id();
        $this->title = $plan->getTitle();
        $this->description = $plan->getDescription();
        $this->icon = $plan->getIcon();
        $this->featureList = $plan->getFeatureList();
        $this->price = $plan->getPrice();
        $this->billingCycle = $plan->getBillingCycle();
        $this->creditCount = $plan->getCreditCount();
        $this->memberCap = $plan->getMemberCap();
        $this->createdAt = new DateTimeImmutable();
        $this->config = $plan->getConfig();
        $this->memberCap = $plan->getMemberCap();
        $this->plan = $plan;
    }

    public function getPlan(): ?PlanEntity
    {
        return $this->plan;
    }

    public function getConfig(): PlanConfig
    {
        if (!$this->config instanceof PlanConfig) {
            $this->config = new PlanConfig(
                array_merge(
                    $this->plan ? $this->plan->getConfig()->toArray() : [],
                    $this->config ?: []
                )
            );
        }

        return $this->config;
    }

    public function resync()
    {
        $this->title = $this->plan->getTitle();
        $this->description = $this->plan->getDescription();
        $this->icon = $this->plan->getIcon();
        $this->featureList = $this->plan->getFeatureList();
        $this->creditCount = $this->plan->getCreditCount();
        $this->memberCap = $this->plan->getMemberCap();
        $this->config = $this->plan->getConfig();
    }

    public function unlink(): void
    {
        $this->plan = null;
    }
}
