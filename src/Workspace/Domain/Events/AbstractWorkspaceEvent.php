<?php

declare(strict_types=1);

namespace Workspace\Domain\Events;

use Workspace\Domain\Entities\WorkspaceEntity;

abstract class AbstractWorkspaceEvent
{
    public function __construct(
        public readonly WorkspaceEntity $workspace,
    ) {
    }
}
