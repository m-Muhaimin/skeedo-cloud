<?php

declare(strict_types=1);

namespace Option\Application\Commands;

use Option\Application\CommandHandlers\CountOptionsCommandHandler;
use Shared\Infrastructure\CommandBus\Attributes\Handler;

#[Handler(CountOptionsCommandHandler::class)]
class CountOptionsCommand
{
}
