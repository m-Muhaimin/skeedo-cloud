<?php

declare(strict_types=1);

namespace Workspace\Application\CommandHandlers;

use Iterator;
use Shared\Domain\ValueObjects\CursorDirection;
use Workspace\Application\Commands\ListWorkspacesCommand;
use Workspace\Domain\Entities\WorkspaceEntity;
use Workspace\Domain\Exceptions\WorkspaceNotFoundException;
use Workspace\Domain\Repositories\WorkspaceRepositoryInterface;

class ListWorkspacesCommandHandler
{

    public function __construct(
        private WorkspaceRepositoryInterface $repo
    ) {
    }

    /**
     * @return Iterator<WorkspaceEntity>
     * @throws WorkspaceNotFoundException
     */
    public function handle(ListWorkspacesCommand $cmd): Iterator
    {
        $cursor = $cmd->cursor
            ? $this->repo->ofId($cmd->cursor)
            : null;

        $workspaces = $this->repo;

        if ($cmd->sortDirection) {
            $workspaces = $workspaces->sort($cmd->sortDirection, $cmd->sortParameter);
        }

        if (is_bool($cmd->hasSubscription)) {
            $workspaces = $workspaces->hasSubscription($cmd->hasSubscription);
        }

        if ($cmd->query) {
            $workspaces = $workspaces->search($cmd->query);
        }

        if ($cmd->maxResults) {
            $workspaces = $workspaces->setMaxResults($cmd->maxResults);
        }

        if ($cursor) {
            if ($cmd->cursorDirection == CursorDirection::ENDING_BEFORE) {
                return $workspaces = $workspaces->endingBefore($cursor);
            }

            return $workspaces->startingAfter($cursor);
        }

        return $workspaces->getIterator();
    }
}
