<?php

declare(strict_types=1);

namespace Workspace\Application\Commands;

use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;
use User\Domain\Entities\UserEntity;
use Workspace\Application\CommandHandlers\SetCurrentWorkspaceCommandHandler;

#[Handler(SetCurrentWorkspaceCommandHandler::class)]
class SetCurrentWorkspaceCommand
{
    public Id|UserEntity $user;
    public Id $workspaceId;

    public function __construct(
        string|Id|UserEntity $user,
        string $workspaceId
    ) {
        $this->user = is_string($user) ? new Id($user) : $user;
        $this->workspaceId = new Id($workspaceId);
    }
}
