<?php

declare(strict_types=1);

namespace Workspace\Domain\Events;

use Workspace\Domain\Entities\WorkspaceInvitationEntity;

abstract class AbstractInvitationEvent
{
    public function __construct(
        public readonly WorkspaceInvitationEntity $invitation,
    ) {
    }
}
