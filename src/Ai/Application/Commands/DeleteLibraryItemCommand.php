<?php

declare(strict_types=1);

namespace Ai\Application\Commands;

use Ai\Application\CommandHandlers\DeleteLibraryItemCommandHandler;
use Ai\Domain\Entities\AbstractLibraryItemEntity;
use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;

#[Handler(DeleteLibraryItemCommandHandler::class)]
class DeleteLibraryItemCommand
{
    public Id|AbstractLibraryItemEntity $item;

    public function __construct(string|AbstractLibraryItemEntity $item)
    {
        $this->item = $item instanceof AbstractLibraryItemEntity
            ? $item
            : new Id($item);
    }
}
