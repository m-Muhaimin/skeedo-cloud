<?php

declare(strict_types=1);

namespace Billing\Application\Commands;

use Billing\Application\CommandHandlers\CreateSubscriptionCommandHandler;
use Billing\Domain\Entities\PlanEntity;
use Billing\Domain\ValueObjects\TrialPeriodDays;
use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;
use Workspace\Domain\Entities\WorkspaceEntity;

#[Handler(CreateSubscriptionCommandHandler::class)]
class CreateSubscriptionCommand
{
    public Id|WorkspaceEntity $workspace;
    public Id|PlanEntity $plan;
    public ?TrialPeriodDays $trialPeriodDays = null;

    public function __construct(
        Id|WorkspaceEntity|string $workspace,
        Id|PlanEntity|string $plan
    ) {
        $this->workspace = is_string($workspace)
            ? new Id($workspace) : $workspace;

        $this->plan = is_string($plan)
            ? new Id($plan) : $plan;
    }

    public function setTrialPeriodDays(int $days): void
    {
        $this->trialPeriodDays = new TrialPeriodDays($days);
    }
}
