<?php

declare(strict_types=1);

namespace Stat\Application\Commands;

use DateTimeInterface;
use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;
use Stat\Application\CommandHandlers\ReadStatCommandHandler;
use Stat\Domain\ValueObjects\StatType;
use Workspace\Domain\Entities\WorkspaceEntity;

#[Handler(ReadStatCommandHandler::class)]
class ReadStatCommand
{
    public StatType $type;
    public null|Id|WorkspaceEntity $workspace = null;
    public ?DateTimeInterface $year = null;
    public ?DateTimeInterface $month = null;
    public ?DateTimeInterface $day = null;
    public ?DateTimeInterface $startDate = null;
    public ?DateTimeInterface $endDate = null;

    public function __construct(string|StatType $type)
    {
        $this->type = $type instanceof StatType ? $type : StatType::from($type);
    }

    public function setWorkspace(string|Id|WorkspaceEntity $workspace): void
    {
        $this->workspace = is_string($workspace) ? new Id($workspace) : $workspace;
    }
}
