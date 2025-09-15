<?php

declare(strict_types=1);

namespace Assistant\Application\Commands;

use Assistant\Application\CommandHandlers\CreateDataUnitCommandHandler;
use Assistant\Domain\Entities\AssistantEntity;
use Dataset\Domain\ValueObjects\Url;
use Psr\Http\Message\UploadedFileInterface;
use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;

#[Handler(CreateDataUnitCommandHandler::class)]
class CreateDataUnitCommand
{
    public Id|AssistantEntity $assistant;
    public ?UploadedFileInterface $file = null;
    public ?Url $url = null;

    public function __construct(
        string|Id|AssistantEntity $assistant,
    ) {
        $this->assistant = is_string($assistant) ? new Id($assistant) : $assistant;
    }

    public function setUrl(string $url): void
    {
        $this->url = new Url($url);
    }
}
