<?php

declare(strict_types=1);

namespace Presentation\AccessControls;

use Easy\Http\Message\StatusCode;
use Exception;
use LogicException;
use Presentation\Exceptions\HttpException;
use Presentation\Exceptions\NotFoundException;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Shared\Infrastructure\CommandBus\Exception\NoHandlerFoundException;
use User\Domain\Entities\UserEntity;
use Workspace\Application\Commands\ReadWorkspaceCommand;
use Workspace\Domain\Entities\WorkspaceEntity;
use Workspace\Domain\Exceptions\WorkspaceNotFoundException;

class WorkspaceAccessControl
{
    public function __construct(
        private Dispatcher $dispatcher
    ) {
    }

    /**
     * @throws NoHandlerFoundException
     * @throws NotFoundException
     * @throws Exception
     * @throws LogicException
     * @throws HttpException
     */
    public function denyUnlessGranted(
        Permission $permission,
        UserEntity $user,
        string|WorkspaceEntity $workspace
    ): void {
        if (is_string($workspace)) {
            $workspace = $this->getWorkspace($workspace);
        }

        $isGranted = match ($permission) {
            Permission::WORKSPACE_MANAGE => $this->canManage($user, $workspace),
            default => false
        };

        if (!$isGranted) {
            throw new HttpException(statusCode: StatusCode::FORBIDDEN);
        }
    }

    private function canManage(UserEntity $user, WorkspaceEntity $workspace): bool
    {
        if (
            $workspace->getOwner()->getId()->getValue()->toString()
            !== $user->getId()->getValue()->toString()
        ) {
            return false;
        }

        return true;
    }

    /**
     * @throws NoHandlerFoundException
     * @throws NotFoundException
     */
    private function getWorkspace(string $id): WorkspaceEntity
    {
        try {
            $cmd = new ReadWorkspaceCommand($id);

            /** @var WorkspaceEntity */
            $workspace = $this->dispatcher->dispatch($cmd);
        } catch (WorkspaceNotFoundException $th) {
            throw new NotFoundException(
                param: 'id',
                previous: $th
            );
        }

        return $workspace;
    }
}
