<?php

declare(strict_types=1);

namespace Preset\Application\Commands;

use Preset\Application\CommandHandlers\DeletePresetCommandHandler;
use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;

#[Handler(DeletePresetCommandHandler::class)]
class DeletePresetCommand
{
    public Id $id;

    public function __construct(
        string $id
    ) {
        $this->id = new Id($id);
    }
}
