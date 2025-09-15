<?php

declare(strict_types=1);

namespace Stat\Application\Commands;

use Shared\Domain\ValueObjects\CursorDirection;
use Shared\Domain\ValueObjects\Id;
use Shared\Domain\ValueObjects\MaxResults;
use Shared\Infrastructure\CommandBus\Attributes\Handler;
use Stat\Application\CommandHandlers\ListStatsCommandHandler;
use Stat\Domain\ValueObjects\StatType;
use Workspace\Domain\Entities\WorkspaceEntity;

#[Handler(ListStatsCommandHandler::class)]
class ListStatsCommand
{
    public StatType $type;
    public null|Id|WorkspaceEntity $workspace = null;
    public ?Id $cursor = null;
    public ?MaxResults $maxResults = null;
    public CursorDirection $cursorDirection = CursorDirection::STARTING_AFTER;

    public function __construct(string|StatType $type)
    {
        $this->type = $type instanceof StatType ? $type : StatType::from($type);
        $this->maxResults = new MaxResults(MaxResults::DEFAULT);
    }

    public function setCursor(
        string $id,
        string $dir = 'starting_after'
    ): self {
        $this->cursor = new Id($id);
        $this->cursorDirection = CursorDirection::from($dir);

        return $this;
    }

    public function setLimit(int $limit): self
    {
        $this->maxResults = new MaxResults($limit);

        return $this;
    }

    public function setWorkspace(string|Id|WorkspaceEntity $workspace): self
    {
        $this->workspace = is_string($workspace)
            ? new Id($workspace)
            : $workspace;

        return $this;
    }
}
