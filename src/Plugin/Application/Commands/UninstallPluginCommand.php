<?php

declare(strict_types=1);

namespace Plugin\Application\Commands;

use Plugin\Application\CommandHandlers\UninstallPluginCommandHandler;
use Plugin\Domain\ValueObjects\Name;
use Shared\Infrastructure\CommandBus\Attributes\Handler;

#[Handler(UninstallPluginCommandHandler::class)]
class UninstallPluginCommand
{
    public Name $name;

    public function __construct(string $name)
    {
        $this->name = new Name($name);
    }
}
