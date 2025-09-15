<?php

declare(strict_types=1);

namespace Ai\Application\CommandHandlers;

use Ai\Application\Commands\ListLibraryItemsCommand;
use Ai\Domain\Entities\AbstractLibraryItemEntity;
use Ai\Domain\Repositories\LibraryItemRepositoryInterface;
use Ai\Domain\ValueObjects\Visibility;
use Shared\Domain\ValueObjects\CursorDirection;
use Traversable;

class ListLibraryItemsCommandHandler
{
    public function __construct(
        private LibraryItemRepositoryInterface $repo,
    ) {}

    /**
     * @return Traversable<AbstractLibraryItemEntity>
     * @throws LibraryItemNotFoundException
     */
    public function handle(ListLibraryItemsCommand $cmd): Traversable
    {
        $cursor = $cmd->cursor
            ? $this->repo->ofId($cmd->cursor)
            : null;

        $items = $this->repo;

        if ($cmd->sortDirection) {
            $items = $items->sort($cmd->sortDirection, $cmd->sortParameter);
        }

        if ($cmd->workspace) {
            $items = $items->filterByWorkspace($cmd->workspace);
        }

        if ($cmd->user) {
            $items = $items->filterByUser(
                $cmd->user,
                $cmd->workspace ?: Visibility::PRIVATE
            );
        }

        if ($cmd->query) {
            $items = $items->search($cmd->query);
        }

        if ($cmd->state) {
            $items = $items->filterByState(...$cmd->state);
        }

        if ($cmd->type) {
            $items = $items->filterByType($cmd->type);
        }

        if ($cmd->model) {
            $items = $items->filterByModel($cmd->model);
        }

        if ($cmd->createdAfter) {
            $items = $items->createdAfter($cmd->createdAfter);
        }

        if ($cmd->createdBefore) {
            $items = $items->createdBefore($cmd->createdBefore);
        }

        if ($cmd->maxResults) {
            $items = $items->setMaxResults($cmd->maxResults);
        }

        if ($cursor) {
            if ($cmd->cursorDirection == CursorDirection::ENDING_BEFORE) {
                return $items = $items->endingBefore($cursor);
            }

            return $items->startingAfter($cursor);
        }

        return $items;
    }
}
