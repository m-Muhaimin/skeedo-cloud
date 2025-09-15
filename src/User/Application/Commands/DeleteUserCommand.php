<?php

declare(strict_types=1);

namespace User\Application\Commands;

use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;
use User\Application\CommandHandlers\DeleteUserCommandHandler;

#[Handler(DeleteUserCommandHandler::class)]
class DeleteUserCommand
{
    public Id $id;

    public function __construct(string $id)
    {
        $this->id = new Id($id);
    }
}
