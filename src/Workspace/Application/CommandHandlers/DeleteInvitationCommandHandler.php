<?php

declare(strict_types=1);

namespace Workspace\Application\CommandHandlers;

use Workspace\Application\Commands\DeleteInvitationCommand;
use Workspace\Domain\Entities\WorkspaceEntity;
use Workspace\Domain\Exceptions\WorkspaceNotFoundException;
use Workspace\Domain\Exceptions\InvitationNotFoundException;
use Workspace\Domain\Repositories\WorkspaceRepositoryInterface;

class DeleteInvitationCommandHandler
{
    public function __construct(
        private WorkspaceRepositoryInterface $repo
    ) {
    }

    /**
     * @throws WorkspaceNotFoundException
     * @throws InvitationNotFoundException
     */
    public function handle(DeleteInvitationCommand $cmd): WorkspaceEntity
    {
        $ws = $this->repo->ofId($cmd->workspaceId);
        $ws->removeInvitation($cmd->id);

        return $ws;
    }
}
