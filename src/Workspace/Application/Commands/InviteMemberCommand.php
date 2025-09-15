<?php

declare(strict_types=1);

namespace Workspace\Application\Commands;

use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;
use Workspace\Application\CommandHandlers\InviteMemberCommandHandler;
use Workspace\Domain\ValueObjects\Email;

#[Handler(InviteMemberCommandHandler::class)]
class InviteMemberCommand
{
    public Id $workspaceId;
    public Email $email;

    public function __construct(
        string $workspaceId,
        string $email
    ) {
        $this->workspaceId = new Id($workspaceId);
        $this->email = new Email($email);
    }
}
