<?php

declare(strict_types=1);

namespace Category\Application\CommandHandlers;

use Category\Application\Commands\ListCategoriesCommand;
use Category\Domain\Entities\CategoryEntity;
use Category\Domain\Exceptions\CategoryNotFoundException;
use Category\Domain\Repositories\CategoryRepositoryInterface;
use Shared\Domain\ValueObjects\CursorDirection;
use Traversable;

class ListCategoriesCommandHandler
{
    public function __construct(
        private CategoryRepositoryInterface $repo
    ) {}

    /**
     * @return Traversable<int,CategoryEntity>
     * @throws CategoryNotFoundException
     */
    public function handle(ListCategoriesCommand $cmd): Traversable
    {

        $cursor = $cmd->cursor
            ? $this->repo->ofId($cmd->cursor)
            : null;

        $categories = $this->repo
            ->sort($cmd->sortDirection, $cmd->sortParameter);

        if ($cmd->query) {
            $categories = $categories->search($cmd->query);
        }

        if ($cmd->maxResults) {
            $categories = $categories->setMaxResults($cmd->maxResults);
        }

        if ($cursor) {
            if ($cmd->cursorDirection == CursorDirection::ENDING_BEFORE) {
                return $categories->endingBefore($cursor);
            }

            return $categories->startingAfter($cursor);
        }

        return $categories->getIterator();
    }
}
