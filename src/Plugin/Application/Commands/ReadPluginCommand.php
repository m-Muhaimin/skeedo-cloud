<?php

declare(strict_types=1);

namespace Plugin\Application\Commands;

use Plugin\Application\CommandHandlers\ReadPluginCommandHandler;
use Plugin\Domain\ValueObjects\Name;
use Shared\Infrastructure\CommandBus\Attributes\Handler;

#[Handler(ReadPluginCommandHandler::class)]
class ReadPluginCommand
{
    public Name $name;

    public function __construct(string $name)
    {
        $this->name = new Name($name);
    }
}
