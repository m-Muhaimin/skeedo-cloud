<?php

declare(strict_types=1);

namespace Stat\Application\Commands;

use Shared\Infrastructure\CommandBus\Attributes\Handler;
use Stat\Application\CommandHandlers\GetDatasetCommandHandler;
use Stat\Domain\ValueObjects\DatasetCategory;

#[Handler(GetDatasetCommandHandler::class)]
class GetDatasetCommand extends ReadStatCommand
{
    public DatasetCategory $category = DatasetCategory::DATE;
}
