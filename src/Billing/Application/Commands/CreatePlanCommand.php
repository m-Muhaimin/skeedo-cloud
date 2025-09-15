<?php

declare(strict_types=1);

namespace Billing\Application\Commands;

use Billing\Application\CommandHandlers\CreatePlanCommandHandler;
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
use Shared\Infrastructure\CommandBus\Attributes\Handler;

#[Handler(CreatePlanCommandHandler::class)]
class CreatePlanCommand
{
    public Title $title;
    public Price $price;
    public BillingCycle $billingCycle;

    public ?Description $description = null;
    public ?CreditCount $creditCount = null;
    public ?Superiority $superiority = null;
    public ?Status $status = null;
    public ?IsFeatured $isFeatured = null;
    public ?Icon $icon = null;
    public ?FeatureList $featureList = null;
    public ?PlanConfig $config = null;
    public ?MemberCap $memberCap = null;

    /**
     * @param string $title 
     * @param int $price 
     * @param string $billingCycle 
     * @return void 
     */
    public function __construct(
        string $title,
        int $price,
        string $billingCycle
    ) {
        $this->title = new Title($title);
        $this->price = new Price($price);
        $this->billingCycle = BillingCycle::from($billingCycle);
    }

    /**
     * @param null|string $description 
     * @return CreatePlanCommand 
     */
    public function setDescription(?string $description): self
    {
        $this->description = new Description($description);
        return $this;
    }

    /**
     * @param null|int $creditCount 
     * @return CreatePlanCommand 
     */
    public function setCreditCount(?int $creditCount): self
    {
        $this->creditCount = new CreditCount($creditCount);
        return $this;
    }

    /**
     * @param int $superiority 
     * @return CreatePlanCommand 
     */
    public function setSuperiority(int $superiority): self
    {
        $this->superiority = new Superiority($superiority);
        return $this;
    }

    /**
     * @param int $status 
     * @return CreatePlanCommand 
     */
    public function setStatus(int $status): self
    {
        $this->status = Status::from($status);
        return $this;
    }

    /**
     * @param bool $isFeatured 
     * @return CreatePlanCommand 
     */
    public function setIsFeatured(bool $isFeatured): self
    {
        $this->isFeatured = new IsFeatured($isFeatured);
        return $this;
    }

    /**
     * @param null|string $icon
     * @return CreatePlanCommand
     */
    public function setIcon(?string $icon): self
    {
        $this->icon = new Icon($icon);
        return $this;
    }

    /**
     * @param string ...$features
     * @return CreatePlanCommand
     */
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

    /**
     * @param null|int $memberCap
     * @return CreatePlanCommand
     */
    public function setMemberCap(?int $memberCap): self
    {
        $this->memberCap = new MemberCap($memberCap);
        return $this;
    }
}
