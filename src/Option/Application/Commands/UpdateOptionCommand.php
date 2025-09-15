<?php

declare(strict_types=1);

namespace Option\Application\Commands;

use Option\Application\CommandHandlers\UpdateOptionCommandHandler;
use Option\Domain\ValueObjects\Value;
use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;

#[Handler(UpdateOptionCommandHandler::class)]
class UpdateOptionCommand
{
    public Id $id;
    public ?Value $value = null;

    public function __construct(string $id)
    {
        $this->id = new Id($id);
    }

    public function setValue(?string $value): void
    {
        $this->value = new Value($value);
    }
}
