<?php

declare(strict_types=1);

namespace Workspace\Application\CommandHandlers;

use Easy\Container\Attributes\Inject;
use Exception;
use Psr\EventDispatcher\EventDispatcherInterface;
use User\Domain\Exceptions\OwnedWorkspaceCapException;
use User\Domain\Repositories\UserRepositoryInterface;
use Workspace\Application\Commands\UpdateWorkspaceCommand;
use Workspace\Domain\Entities\WorkspaceEntity;
use Workspace\Domain\Events\WorkspaceUpdatedEvent;
use Workspace\Domain\Exceptions\WorkspaceNotFoundException;
use Workspace\Domain\Exceptions\WorkspaceUserNotFoundException;
use Workspace\Domain\Repositories\WorkspaceRepositoryInterface;

class UpdateWorkspaceCommandHandler
{
    public function __construct(
        private WorkspaceRepositoryInterface $repo,
        private UserRepositoryInterface $userRepo,
        private EventDispatcherInterface $dispatcher,

        #[Inject('option.site.workspace_cap')]
        private null|string|int $workspaceCap = null,
    ) {}

    /**
     * @throws WorkspaceNotFoundException
     * @throws WorkspaceUserNotFoundException
     * @throws Exception
     */
    public function handle(UpdateWorkspaceCommand $cmd): WorkspaceEntity
    {
        $ws = $this->repo->ofId($cmd->id);

        if ($cmd->name) {
            $ws->setName($cmd->name);
        }

        if ($cmd->ownerId) {
            $user = $this->userRepo->ofId($cmd->ownerId);

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

            $ws->setOwner($user);
        }

        if ($cmd->address) {
            $ws->setAddress($cmd->address);
        }

        if ($cmd->openaiApiKey) {
            $ws->setOpenaiApiKey($cmd->openaiApiKey);
        }

        if ($cmd->anthropicApiKey) {
            $ws->setAnthropicApiKey($cmd->anthropicApiKey);
        }

        if ($cmd->creditCount) {
            $ws->setCreditCount($cmd->creditCount);
        }

        // Dispatch the workspace updated event
        $event = new WorkspaceUpdatedEvent($ws);
        $this->dispatcher->dispatch($event);

        return $ws;
    }
}
