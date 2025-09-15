<?php

declare(strict_types=1);

namespace Assistant\Application\Commands;

use Assistant\Application\CommandHandlers\CountAssistantsCommandHandler;
use Assistant\Domain\ValueObjects\Status;
use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;

#[Handler(CountAssistantsCommandHandler::class)]
class CountAssistantsCommand
{
    public ?Status $status = null;

    /** @var null|array<Id> */
    public ?array $ids = null;

    /** Search terms/query */
    public ?string $query = null;

    public function setStatus(int $status): self
    {
        $this->status = Status::from($status);
        return $this;
    }

    public function setIds(string|Id ...$ids): void
    {
        $this->ids = array_map(
            fn($id) => is_string($id) ? new Id($id) : $id,
            $ids
        );
    }
}
