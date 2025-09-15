<?php

declare(strict_types=1);

namespace Option\Application\Commands;

use Option\Application\CommandHandlers\ListOptionsCommandHandler;
use Shared\Infrastructure\CommandBus\Attributes\Handler;

#[Handler(ListOptionsCommandHandler::class)]
class ListOptionsCommand extends CountOptionsCommand
{
}
