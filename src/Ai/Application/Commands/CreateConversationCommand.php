<?php

declare(strict_types=1);

namespace Ai\Application\Commands;

use Ai\Application\CommandHandlers\CreateConversationCommandHandler;
use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

#[Handler(CreateConversationCommandHandler::class)]
class CreateConversationCommand
{
    public Id|WorkspaceEntity $workspace;
    public Id|UserEntity $user;

    public function __construct(
        string|Id|WorkspaceEntity $workspace,
        string|Id|UserEntity $user
    ) {
        $this->workspace = is_string($workspace) ? new Id($workspace) : $workspace;
        $this->user = is_string($user) ? new Id($user) : $user;
    }
}
