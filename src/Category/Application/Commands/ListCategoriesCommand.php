<?php

declare(strict_types=1);

namespace Category\Application\Commands;

use Category\Application\CommandHandlers\ListCategoriesCommandHandler;
use Category\Domain\ValueObjects\SortParameter;
use Shared\Domain\ValueObjects\CursorDirection;
use Shared\Domain\ValueObjects\Id;
use Shared\Domain\ValueObjects\MaxResults;
use Shared\Domain\ValueObjects\SortDirection;
use Shared\Infrastructure\CommandBus\Attributes\Handler;

#[Handler(ListCategoriesCommandHandler::class)]
class ListCategoriesCommand extends CountCategoriesCommand
{
    public ?SortParameter $sortParameter = null;
    public SortDirection $sortDirection = SortDirection::DESC;
    public ?Id $cursor = null;
    public ?MaxResults $maxResults = null;
    public CursorDirection $cursorDirection = CursorDirection::STARTING_AFTER;

    public function setOrderBy(string $orderBy, string $dir): void
    {
        $this->sortParameter =  SortParameter::from($orderBy);
        $this->sortDirection = SortDirection::from(strtoupper($dir));
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
}
