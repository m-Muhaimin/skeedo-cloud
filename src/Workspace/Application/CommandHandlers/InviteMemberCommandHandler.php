<?php

declare(strict_types=1);

namespace Workspace\Application\CommandHandlers;

use Psr\EventDispatcher\EventDispatcherInterface;
use Workspace\Application\Commands\InviteMemberCommand;
use Workspace\Domain\Entities\WorkspaceInvitationEntity;
use Workspace\Domain\Events\InvitationCreatedEvent;
use Workspace\Domain\Exceptions\WorkspaceNotFoundException;
use Workspace\Domain\Exceptions\MemberAlreadyJoinedException;
use Workspace\Domain\Repositories\WorkspaceRepositoryInterface;

class InviteMemberCommandHandler
{
    public function __construct(
        private WorkspaceRepositoryInterface $repo,
        private EventDispatcherInterface $dispatcher
    ) {
    }

    /**
     * @throws WorkspaceNotFoundException
     * @throws MemberAlreadyJoinedException
     */
    public function handle(InviteMemberCommand $cmd): WorkspaceInvitationEntity
    {
        $ws = $this->repo->ofId($cmd->workspaceId);
        $inv = $ws->invite($cmd->email);

        // Dispatch event
        $event = new InvitationCreatedEvent($inv);
        $this->dispatcher->dispatch($event);

        return $inv;
    }
}
