<?php

declare(strict_types=1);

namespace Ai\Application\Commands;

use Ai\Application\CommandHandlers\UpdateLibraryItemCommandHandler;
use Ai\Domain\Entities\AbstractLibraryItemEntity;
use Ai\Domain\ValueObjects\Content;
use Ai\Domain\ValueObjects\Title;
use Ai\Domain\ValueObjects\Visibility;
use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;

#[Handler(UpdateLibraryItemCommandHandler::class)]
class UpdateLibraryItemCommand
{
    public Id|AbstractLibraryItemEntity $id;

    public ?Title $title = null;
    public ?Content $content = null;
    public ?Visibility $visibility = null;

    public function __construct(
        string|Id|AbstractLibraryItemEntity $id,
    ) {
        $this->id = is_string($id) ? new Id($id) : $id;
    }

    public function setTitle(string $title): void
    {
        $this->title = new Title($title);
    }

    public function setContent(?string $content): void
    {
        $this->content = new Content($content);
    }

    public function setVisibility(int $visibility): void
    {
        $this->visibility = Visibility::from($visibility);
    }
}
