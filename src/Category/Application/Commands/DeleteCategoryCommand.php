<?php

declare(strict_types=1);

namespace Category\Application\Commands;

use Category\Application\CommandHandlers\DeleteCategoryCommandHandler;
use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;

#[Handler(DeleteCategoryCommandHandler::class)]
class DeleteCategoryCommand
{
    public Id $id;

    public function __construct(string $id)
    {
        $this->id = new Id($id);
    }
}
