<?php

declare(strict_types=1);

namespace Plugin\Application\Commands;

use Plugin\Application\CommandHandlers\UpdatePluginCommandHandler;
use Plugin\Domain\ValueObjects\Name;
use Plugin\Domain\ValueObjects\Status;
use Shared\Infrastructure\CommandBus\Attributes\Handler;

#[Handler(UpdatePluginCommandHandler::class)]
class UpdatePluginCommand
{
    public ?Status $status = null;
    public Name $name;

    public function __construct(string $name)
    {
        $this->name = new Name($name);
    }

    public function setStatus(string $status): void
    {
        $this->status = Status::from($status);
    }
}
