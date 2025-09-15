<?php

declare(strict_types=1);

namespace Plugin\Application\Commands;

use Plugin\Application\CommandHandlers\ListPluginsCommandHandler;
use Shared\Infrastructure\CommandBus\Attributes\Handler;

#[Handler(ListPluginsCommandHandler::class)]
class ListPluginsCommand extends CountPluginsCommand
{
}
