<?php

declare(strict_types=1);

namespace Workspace\Application\Commands;

use Shared\Infrastructure\CommandBus\Attributes\Handler;
use Workspace\Application\CommandHandlers\CountWorkspacesCommandHandler;

#[Handler(CountWorkspacesCommandHandler::class)]
class CountWorkspacesCommand
{
    public ?bool $hasSubscription = null;

    /** Search terms/query */
    public ?string $query = null;
}
