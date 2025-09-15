<?php

declare(strict_types=1);

namespace Workspace\Application\CommandHandlers;

use Exception;
use Workspace\Application\Commands\DeleteWorkspaceUserCommand;
use Workspace\Domain\Entities\WorkspaceEntity;
use Workspace\Domain\Exceptions\WorkspaceNotFoundException;
use Workspace\Domain\Exceptions\WorkspaceUserNotFoundException;
use Workspace\Domain\Repositories\WorkspaceRepositoryInterface;

class DeleteWorkspaceUserCommandHandler
{
    public function __construct(
        private WorkspaceRepositoryInterface $repo
    ) {
    }

    /**
     * @throws WorkspaceNotFoundException
     * @throws WorkspaceUserNotFoundException
     * @throws Exception
     */
    public function handle(DeleteWorkspaceUserCommand $cmd): WorkspaceEntity
    {
        $ws = $this->repo->ofId($cmd->workspaceId);
        $ws->removeUser($cmd->userId);

        return $ws;
    }
}
