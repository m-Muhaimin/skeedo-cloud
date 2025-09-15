<?php

declare(strict_types=1);

namespace Workspace\Application\CommandHandlers;

use Workspace\Application\Commands\SetCurrentWorkspaceCommand;
use Workspace\Domain\Exceptions\WorkspaceNotFoundException;

class SetCurrentWorkspaceCommandHandler
{
    /**
     * @throws WorkspaceNotFoundException
     */
    public function handle(SetCurrentWorkspaceCommand $cmd): void
    {
        $user = $cmd->user;
        $workspaceId = $cmd->workspaceId;

        $ws = $user->getWorkspaceById($workspaceId);
        $user->setCurrentWorkspace($ws);
    }
}
