<?php

declare(strict_types=1);

namespace Workspace\Application\CommandHandlers;

use Workspace\Application\Commands\AcceptInvitationCommand;
use Workspace\Domain\Entities\WorkspaceEntity;
use Workspace\Domain\Exceptions\WorkspaceNotFoundException;
use Workspace\Domain\Exceptions\InvitationNotFoundException;
use Workspace\Domain\Repositories\WorkspaceRepositoryInterface;

class AcceptInvitationCommandHandler
{
    public function __construct(
        private WorkspaceRepositoryInterface $repo
    ) {
    }

    /**
     * @throws WorkspaceNotFoundException
     * @throws InvitationNotFoundException
     */
    public function handle(AcceptInvitationCommand $cmd): WorkspaceEntity
    {
        $ws = $this->repo->ofId($cmd->workspaceId);
        $ws->acceptInvitation($cmd->id, $cmd->user);

        $cmd->user->setCurrentWorkspace($ws);

        return $ws;
    }
}
