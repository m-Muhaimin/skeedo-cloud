<?php

declare(strict_types=1);

namespace Plugin\Application\Commands;

use Plugin\Application\CommandHandlers\CountPluginsCommandHandler;
use Plugin\Domain\ValueObjects\Status;
use Plugin\Domain\ValueObjects\Type;
use Shared\Infrastructure\CommandBus\Attributes\Handler;

#[Handler(CountPluginsCommandHandler::class)]
class CountPluginsCommand
{
    public ?Type $type = null;
    public ?Status $status = null;

    /** Search terms/query */
    public ?string $query = null;

    public function setStatus(string $status): void
    {
        $this->status = Status::from($status);
    }
}
