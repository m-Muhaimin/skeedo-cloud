<?php

declare(strict_types=1);

namespace Option\Application\Commands;

use Option\Application\CommandHandlers\CreateOptionCommandHandler;
use Option\Domain\ValueObjects\Key;
use Option\Domain\ValueObjects\Value;
use Shared\Infrastructure\CommandBus\Attributes\Handler;

#[Handler(CreateOptionCommandHandler::class)]
class CreateOptionCommand
{
    public Key $key;
    public Value $value;

    public function __construct(string $key, ?string $value = null)
    {
        $this->key = new Key($key);
        $this->value = new Value($value);
    }
}
