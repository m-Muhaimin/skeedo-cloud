<?php

declare(strict_types=1);

namespace Workspace\Application\Commands;

use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;
use User\Domain\Entities\UserEntity;
use Workspace\Application\CommandHandlers\AcceptInvitationCommandHandler;

#[Handler(AcceptInvitationCommandHandler::class)]
class AcceptInvitationCommand
{
    public Id $workspaceId;
    public Id $id;

    public function __construct(
        public UserEntity $user,
        string $workspaceId,
        string $id
    ) {
        $this->workspaceId = new Id($workspaceId);
        $this->id = new Id($id);
    }
}
