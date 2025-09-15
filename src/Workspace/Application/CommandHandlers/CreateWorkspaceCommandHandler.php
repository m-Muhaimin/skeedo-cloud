<?php

declare(strict_types=1);

namespace Workspace\Application\CommandHandlers;

use Easy\Container\Attributes\Inject;
use Psr\EventDispatcher\EventDispatcherInterface;
use Workspace\Application\Commands\CreateWorkspaceCommand;
use User\Domain\Exceptions\OwnedWorkspaceCapException;
use Workspace\Domain\Entities\WorkspaceEntity;
use Workspace\Domain\Events\WorkspaceCreatedEvent;
use Workspace\Domain\Repositories\WorkspaceRepositoryInterface;

class CreateWorkspaceCommandHandler
{
    public function __construct(
        private WorkspaceRepositoryInterface $repo,
        private EventDispatcherInterface $dispatcher,

        #[Inject('option.site.workspace_cap')]
        private null|string|int $workspaceCap = null,
    ) {}

    public function handle(CreateWorkspaceCommand $cmd): WorkspaceEntity
    {
        $user = $cmd->user;

        $cap = $user->getWorkspaceCap()->value;

        if ($cap === 0) {
            $cap = $this->workspaceCap;

            if (is_string($cap) && $cap == '') {
                $cap = null;
            }
        }

        if (
            $cap !== null
            && iterator_count($user->getOwnedWorkspaces()) >= $cap
        ) {
            throw new OwnedWorkspaceCapException();
        }

        $workspace = $user->createWorkspace($cmd->name);

        if ($cmd->address) {
            $workspace->setAddress($cmd->address);
        }

        // Add the workspace to the repository
        $this->repo->add($workspace);

        // Dispatch the workspace created event
        $event = new WorkspaceCreatedEvent($workspace);
        $this->dispatcher->dispatch($event);

        return $workspace;
    }
}
