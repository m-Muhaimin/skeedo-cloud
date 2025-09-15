<?php

declare(strict_types=1);

namespace Stat\Application\CommandHandlers;

use Shared\Domain\ValueObjects\Id;
use Stat\Application\Commands\ReadStatCommand;
use Stat\Domain\Repositories\StatRepositoryInterface;
use Workspace\Domain\Repositories\WorkspaceRepositoryInterface;

class ReadStatCommandHandler
{
    public function __construct(
        private StatRepositoryInterface $repo,
        private WorkspaceRepositoryInterface $workspaceRepo
    ) {}

    public function handle(ReadStatCommand $cmd): int
    {
        $stats = $this->repo->filterByType($cmd->type);

        if ($cmd->workspace) {
            $ws = $cmd->workspace instanceof Id
                ? $this->workspaceRepo->ofId($cmd->workspace)
                : $cmd->workspace;

            $stats = $stats->filterByWorkspace($ws);
        }

        if ($cmd->year) {
            $stats = $stats->filterByYear($cmd->year);
        } else if ($cmd->month) {
            $stats = $stats->filterByMonth($cmd->month);
        } else if ($cmd->day) {
            $stats = $stats->filterByDay($cmd->day);
        } else {
            if ($cmd->startDate) {
                $stats = $stats->filterByStartDate($cmd->startDate);
            }

            if ($cmd->endDate) {
                $stats = $stats->filterByEndDate($cmd->endDate);
            }
        }

        return $stats->stat();
    }
}
