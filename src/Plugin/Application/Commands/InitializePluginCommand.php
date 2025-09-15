<?php

declare(strict_types=1);

namespace Plugin\Application\Commands;

use Plugin\Application\CommandHandlers\InitializePluginCommandHandler;
use Plugin\Domain\ValueObjects\Name;
use Shared\Infrastructure\CommandBus\Attributes\Handler;

#[Handler(InitializePluginCommandHandler::class)]
class InitializePluginCommand
{
    public Name $name;

    public function __construct(string $name)
    {
        $this->name = new Name($name);
    }
}
