<?php

declare(strict_types=1);

namespace Billing\Application\Commands;

use Billing\Application\CommandHandlers\ReadOrderCommandHandler;
use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;

#[Handler(ReadOrderCommandHandler::class)]
class ReadOrderCommand
{
    public Id $id;

    public function __construct(Id|string $id)
    {
        $this->id = is_string($id)
            ? new Id($id) : $id;
    }
}
