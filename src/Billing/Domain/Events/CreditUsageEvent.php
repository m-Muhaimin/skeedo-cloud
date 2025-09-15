<?php

declare(strict_types=1);

namespace Billing\Domain\Events;

use Billing\Domain\ValueObjects\CreditCount;
use Workspace\Domain\Entities\WorkspaceEntity;

class CreditUsageEvent
{
    public function __construct(
        public readonly WorkspaceEntity $workspace,
        public readonly CreditCount $count,
    ) {}
}
