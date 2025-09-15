<?php

declare(strict_types=1);

namespace Category\Application\Commands;

use Category\Application\CommandHandlers\CreateCategoryCommandHandler;
use Category\Domain\ValueObjects\Title;
use Shared\Infrastructure\CommandBus\Attributes\Handler;

#[Handler(CreateCategoryCommandHandler::class)]
class CreateCategoryCommand
{
    public Title $title;

    public function __construct(string $title)
    {
        $this->title = new Title($title);
    }
}
