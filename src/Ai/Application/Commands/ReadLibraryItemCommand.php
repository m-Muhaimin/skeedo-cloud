<?php

declare(strict_types=1);

namespace Ai\Application\Commands;

use Ai\Application\CommandHandlers\ReadLibraryItemCommandHandler;
use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;

#[Handler(ReadLibraryItemCommandHandler::class)]
class ReadLibraryItemCommand
{
    public Id $item;

    public function __construct(string $item)
    {
        $this->item = new Id($item);
    }
}
