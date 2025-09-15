<?php

declare(strict_types=1);

namespace Workspace\Application\CommandHandlers;

use Workspace\Application\Commands\ReadWorkspaceCommand;
use Workspace\Domain\Entities\WorkspaceEntity;
use Workspace\Domain\Exceptions\WorkspaceNotFoundException;
use Workspace\Domain\Repositories\WorkspaceRepositoryInterface;

class ReadWorkspaceCommandHandler
{
    public function __construct(
        private WorkspaceRepositoryInterface $repo,
    ) {
    }

    /**
     * @throws WorkspaceNotFoundException
     */
    public function handle(ReadWorkspaceCommand $cmd): WorkspaceEntity
    {
        return $this->repo->ofId($cmd->id);
    }
}
