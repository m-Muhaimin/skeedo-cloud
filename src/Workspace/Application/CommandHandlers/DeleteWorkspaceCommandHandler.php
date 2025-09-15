<?php

declare(strict_types=1);

namespace Workspace\Application\CommandHandlers;

use Psr\EventDispatcher\EventDispatcherInterface;
use Workspace\Application\Commands\DeleteWorkspaceCommand;
use Workspace\Domain\Entities\WorkspaceEntity;
use Workspace\Domain\Events\WorkspaceDeletedEvent;
use Workspace\Domain\Exceptions\WorkspaceNotFoundException;
use Workspace\Domain\Repositories\WorkspaceRepositoryInterface;

class DeleteWorkspaceCommandHandler
{
    public function __construct(
        private WorkspaceRepositoryInterface $repo,
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    /**
     * @throws WorkspaceNotFoundException
     */
    public function handle(DeleteWorkspaceCommand $cmd): void
    {
        $ws = $cmd->ws instanceof WorkspaceEntity
            ? $cmd->ws
            : $this->repo->ofId($cmd->ws);

        $this->repo->remove($ws);

        // Dispatch the workspace created event
        $event = new WorkspaceDeletedEvent($ws);
        $this->dispatcher->dispatch($event);
    }
}
