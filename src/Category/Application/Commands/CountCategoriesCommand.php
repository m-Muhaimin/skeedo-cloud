<?php

declare(strict_types=1);

namespace Category\Application\Commands;

use Category\Application\CommandHandlers\CountCategoriesCommandHandler;
use Shared\Infrastructure\CommandBus\Attributes\Handler;

#[Handler(CountCategoriesCommandHandler::class)]
class CountCategoriesCommand
{
    /** Search terms/query */
    public ?string $query = null;
}
