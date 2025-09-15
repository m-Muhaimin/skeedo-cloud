<?php

declare(strict_types=1);

namespace Billing\Application\Commands;

use Billing\Application\CommandHandlers\CountSubscriptionsCommandHandler;
use Billing\Domain\Entities\PlanEntity;
use Billing\Domain\ValueObjects\SubscriptionStatus;
use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;
use Workspace\Domain\Entities\WorkspaceEntity;

#[Handler(CountSubscriptionsCommandHandler::class)]
class CountSubscriptionsCommand
{
    public ?SubscriptionStatus $status = null;
    public null|Id|WorkspaceEntity $workspace = null;
    public null|Id|PlanEntity $plan = null;

    public function setStatus(string $status): self
    {
        $this->status = SubscriptionStatus::from($status);

        return $this;
    }

    public function setWorkspace(string|Id|WorkspaceEntity $workspace): self
    {

        $this->workspace = is_string($workspace) ? new Id($workspace) : $workspace;
        return $this;
    }

    public function setPlan(string|Id|PlanEntity $plan): self
    {
        $this->plan = is_string($plan) ? new Id($plan) : $plan;
        return $this;
    }
}
