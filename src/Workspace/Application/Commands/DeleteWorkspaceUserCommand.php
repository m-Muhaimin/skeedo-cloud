<?php

declare(strict_types=1);

namespace Workspace\Application\Commands;

use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;
use Workspace\Application\CommandHandlers\DeleteWorkspaceUserCommandHandler;

#[Handler(DeleteWorkspaceUserCommandHandler::class)]
class DeleteWorkspaceUserCommand
{
    public Id $workspaceId;
    public Id $userId;

    public function __construct(
        string $workspaceId,
        string $userId,
    ) {
        $this->workspaceId = new Id($workspaceId);
        $this->userId = new Id($userId);
    }
}
