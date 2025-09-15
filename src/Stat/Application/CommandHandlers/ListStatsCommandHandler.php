<?php

declare(strict_types=1);

namespace Stat\Application\CommandHandlers;

use Shared\Domain\ValueObjects\CursorDirection;
use Shared\Domain\ValueObjects\Id;
use Shared\Domain\ValueObjects\SortDirection;
use Stat\Application\Commands\ListStatsCommand;
use Stat\Domain\Repositories\StatRepositoryInterface;
use Traversable;
use Workspace\Domain\Repositories\WorkspaceRepositoryInterface;

class ListStatsCommandHandler
{
    public function __construct(
        private StatRepositoryInterface $repo,
        private WorkspaceRepositoryInterface $workspaceRepo
    ) {}

    public function handle(ListStatsCommand $cmd): Traversable
    {
        $cursor = $cmd->cursor
            ? $this->repo->ofId($cmd->cursor)
            : null;

        $stats = $this->repo
            ->filterByType($cmd->type)
            ->sort(SortDirection::DESC);

        if ($cmd->workspace) {
            $ws = $cmd->workspace instanceof Id
                ? $this->workspaceRepo->ofId($cmd->workspace)
                : $cmd->workspace;

            $stats = $stats->filterByWorkspace($ws);
        }

        if ($cmd->maxResults) {
            $stats = $stats->setMaxResults($cmd->maxResults);
        }

        if ($cursor) {
            if ($cmd->cursorDirection == CursorDirection::ENDING_BEFORE) {
                return $stats->endingBefore($cursor);
            }

            return $stats->startingAfter($cursor);
        }

        return $stats->getIterator();
    }
}
