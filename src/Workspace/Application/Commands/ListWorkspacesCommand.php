<?php

declare(strict_types=1);

namespace Workspace\Application\Commands;

use Shared\Domain\ValueObjects\CursorDirection;
use Shared\Domain\ValueObjects\Id;
use Shared\Domain\ValueObjects\MaxResults;
use Shared\Domain\ValueObjects\SortDirection;
use Shared\Infrastructure\CommandBus\Attributes\Handler;
use Workspace\Application\CommandHandlers\ListWorkspacesCommandHandler;
use Workspace\Domain\ValueObjects\SortParameter;

#[Handler(ListWorkspacesCommandHandler::class)]
class ListWorkspacesCommand extends CountWorkspacesCommand
{
    public ?SortParameter $sortParameter = null;
    public ?SortDirection $sortDirection = SortDirection::DESC;
    public ?Id $cursor = null;
    public ?MaxResults $maxResults;
    public CursorDirection $cursorDirection = CursorDirection::STARTING_AFTER;

    public function __construct()
    {
        $this->maxResults = new MaxResults(MaxResults::DEFAULT);
    }

    public function setOrderBy(string $orderBy, string $dir): void
    {
        $this->sortParameter =  SortParameter::from($orderBy);
        $this->sortDirection = SortDirection::from(strtoupper($dir));
    }

    public function setCursor(string $id, string $dir = 'starting_after'): self
    {
        $this->cursor = new Id($id);
        $this->cursorDirection = CursorDirection::from($dir);

        return $this;
    }

    public function setLimit(int $limit): self
    {
        $this->maxResults = new MaxResults($limit);

        return $this;
    }
}
