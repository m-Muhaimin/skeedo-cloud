<?php

declare(strict_types=1);

namespace File\Application\Commands;

use File\Application\CommandHandlers\ReadFileCommandHandler;
use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;

#[Handler(ReadFileCommandHandler::class)]
class ReadFileCommand
{
    public Id $id;

    public function __construct(string $id)
    {
        $this->id = new Id($id);
    }
}
