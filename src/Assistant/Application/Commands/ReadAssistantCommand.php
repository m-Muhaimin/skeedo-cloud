<?php

declare(strict_types=1);

namespace Assistant\Application\Commands;

use Assistant\Application\CommandHandlers\ReadAssistantCommandHandler;
use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;

#[Handler(ReadAssistantCommandHandler::class)]
class ReadAssistantCommand
{
    public Id $id;

    public function __construct(string $id)
    {
        $this->id = new Id($id);
    }
}
