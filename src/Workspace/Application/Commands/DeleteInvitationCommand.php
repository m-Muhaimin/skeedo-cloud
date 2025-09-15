<?php

declare(strict_types=1);

namespace Workspace\Application\Commands;

use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;
use Workspace\Application\CommandHandlers\DeleteInvitationCommandHandler;

#[Handler(DeleteInvitationCommandHandler::class)]
class DeleteInvitationCommand
{
    public Id $workspaceId;
    public Id $id;

    public function __construct(
        string $workspaceId,
        string $id
    ) {
        $this->workspaceId = new Id($workspaceId);
        $this->id = new Id($id);
    }
}
