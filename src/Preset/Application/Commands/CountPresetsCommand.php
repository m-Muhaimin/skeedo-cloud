<?php

declare(strict_types=1);

namespace Preset\Application\Commands;

use Category\Domain\Entities\CategoryEntity;
use Preset\Application\CommandHandlers\CountPresetsCommandHandler;
use Preset\Domain\ValueObjects\Status;
use Preset\Domain\ValueObjects\Type;
use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;

#[Handler(CountPresetsCommandHandler::class)]
class CountPresetsCommand
{
    public ?Status $status = null;
    public ?Type $type = null;
    public ?bool $isLocked = null;
    public Id|CategoryEntity|null $category = null;

    /** @var null|array<Id> */
    public ?array $ids = null;

    /** Search terms/query */
    public ?string $query = null;

    public function setIds(string|Id ...$ids): void
    {
        $this->ids = array_map(
            fn($id) => is_string($id) ? new Id($id) : $id,
            $ids
        );
    }
}
