<?php

declare(strict_types=1);

namespace Plugin\Application\Commands;

use Plugin\Application\CommandHandlers\InstallPluginCommandHandler;
use Plugin\Domain\ValueObjects\Name;
use Shared\Infrastructure\CommandBus\Attributes\Handler;

#[Handler(InstallPluginCommandHandler::class)]
class InstallPluginCommand
{
    public Name $name;

    public function __construct(string|Name $name)
    {
        $this->name = $name instanceof Name ? $name : new Name($name);
    }
}
