<?php

declare(strict_types=1);

namespace Workspace\Application\Commands;

use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;
use Workspace\Application\CommandHandlers\DeleteWorkspaceCommandHandler;
use Workspace\Domain\Entities\WorkspaceEntity;

#[Handler(DeleteWorkspaceCommandHandler::class)]
class DeleteWorkspaceCommand
{
    public Id|WorkspaceEntity $ws;

    public function __construct(string|Id|WorkspaceEntity $ws)
    {
        $this->ws = is_string($ws) ? new Id($ws) : $ws;
    }
}
