<?php

declare(strict_types=1);

namespace Assistant\Application\Commands;

use Assistant\Application\CommandHandlers\DeleteDataUnitCommandHandler;
use Assistant\Domain\Entities\AssistantEntity;
use Dataset\Domain\Entities\AbstractDataUnitEntity;
use Psr\Http\Message\UploadedFileInterface;
use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;

#[Handler(DeleteDataUnitCommandHandler::class)]
class DeleteDataUnitCommand
{
    public Id|AssistantEntity $assistant;
    public Id|AbstractDataUnitEntity $unit;

    public function __construct(
        string|Id|AssistantEntity $assistant,
        string|Id|AbstractDataUnitEntity $unit
    ) {
        $this->assistant = is_string($assistant) ? new Id($assistant) : $assistant;
        $this->unit = is_string($unit) ? new Id($unit) : $unit;
    }
}
