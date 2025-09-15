<?php

declare(strict_types=1);

namespace Workspace\Application\Commands;

use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;
use Workspace\Application\CommandHandlers\ReadWorkspaceCommandHandler;

#[Handler(ReadWorkspaceCommandHandler::class)]
class ReadWorkspaceCommand
{
    public Id $id;

    public function __construct(string|Id $id)
    {
        $this->id = is_string($id) ? new Id($id) : $id;
    }
}
