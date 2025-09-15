<?php

declare(strict_types=1);

namespace Voice\Application\Commands;

use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;
use Voice\Application\CommandHandlers\DeleteVoiceCommandHandler;

#[Handler(DeleteVoiceCommandHandler::class)]
class DeleteVoiceCommand
{
    public Id $id;

    public function __construct(string $id)
    {
        $this->id = new Id($id);
    }
}
