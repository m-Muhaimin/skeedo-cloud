<?php

declare(strict_types=1);

namespace Billing\Application\Commands;

use Billing\Application\CommandHandlers\UpdatePlanCommandHandler;
use Billing\Domain\ValueObjects\BillingCycle;
use Billing\Domain\ValueObjects\CreditCount;
use Billing\Domain\ValueObjects\Description;
use Billing\Domain\ValueObjects\FeatureList;
use Billing\Domain\ValueObjects\Icon;
use Billing\Domain\ValueObjects\IsFeatured;
use Billing\Domain\ValueObjects\MemberCap;
use Billing\Domain\ValueObjects\PlanConfig;
use Billing\Domain\ValueObjects\Price;
use Billing\Domain\ValueObjects\Status;
use Billing\Domain\ValueObjects\Superiority;
use Billing\Domain\ValueObjects\Title;
use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;

#[Handler(UpdatePlanCommandHandler::class)]
class UpdatePlanCommand
{
    public Id $id;

    public ?Title $title = null;
    public ?Price $price = null;
    public ?BillingCycle $billingCycle = null;
    public ?Description $description = null;
    public ?CreditCount $creditCount = null;
    public ?Superiority $superiority = null;
    public ?Status $status = null;
    public ?IsFeatured $isFeatured = null;
    public ?Icon $icon = null;
    public ?FeatureList $featureList = null;
    public ?PlanConfig $config = null;
    public ?bool $updateSnapshots = null;
    public ?MemberCap $memberCap = null;

    public function __construct(string $id)
    {
        $this->id = new Id($id);
    }

    public function setTitle(string $title): self
    {
        $this->title = new Title($title);
        return $this;
    }

    public function setPrice(int $price): self
    {
        $this->price = new Price($price);
        return $this;
    }

    public function setBillingCycle(string $billingCycle): self
    {
        $this->billingCycle = BillingCycle::from($billingCycle);
        return $this;
    }

    public function setDescription(?string $description): self
    {
        $this->description = new Description($description);
        return $this;
    }

    public function setCreditCount(?int $creditCount): self
    {
        $this->creditCount = new CreditCount($creditCount);
        return $this;
    }

    public function setSuperiority(int $superiority): self
    {
        $this->superiority = new Superiority($superiority);
        return $this;
    }

    public function setStatus(int $status): self
    {
        $this->status = Status::from($status);
        return $this;
    }

    public function setIsFeatured(bool $isFeatured): self
    {
        $this->isFeatured = new IsFeatured($isFeatured);
        return $this;
    }

    public function setIcon(?string $icon): self
    {
        $this->icon = new Icon($icon);
        return $this;
    }

    public function setFeatureList(string ...$features): self
    {
        $this->featureList = new FeatureList(...$features);
        return $this;
    }

    public function setConfig(array $config): self
    {
        $this->config = new PlanConfig($config);
        return $this;
    }

    public function setMemberCap(?int $memberCap): self
    {
        $this->memberCap = new MemberCap($memberCap);
        return $this;
    }
}
