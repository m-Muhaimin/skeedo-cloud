<?php

declare(strict_types=1);

namespace Billing\Application\Commands;

use Billing\Application\CommandHandlers\ResyncPlanSnapshotCommandHandler;
use Billing\Domain\Entities\PlanSnapshotEntity;
use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;

#[Handler(ResyncPlanSnapshotCommandHandler::class)]
class ResyncPlanSnapshotCommand
{
    public Id|PlanSnapshotEntity $snapshot;

    public function __construct(string|Id|PlanSnapshotEntity $snapshot)
    {
        $this->snapshot = is_string($snapshot) ? new Id($snapshot) : $snapshot;
    }
}
