<?php

declare(strict_types=1);

namespace Billing\Application\CommandHandlers;

use Billing\Application\Commands\ResyncPlanSnapshotCommand;
use Billing\Domain\Entities\PlanSnapshotEntity;
use Billing\Domain\Exceptions\PlanSnapshotNotFoundException;
use Billing\Domain\Repositories\PlanSnapshotRepositoryInterface;

class ResyncPlanSnapshotCommandHandler
{
    public function __construct(
        private PlanSnapshotRepositoryInterface $repo
    ) {
    }

    /**
     * @throws PlanSnapshotNotFoundException
     */
    public function handle(ResyncPlanSnapshotCommand $cmd): PlanSnapshotEntity
    {
        $snapshot = $cmd->snapshot instanceof PlanSnapshotEntity
            ? $cmd->snapshot
            : $this->repo->ofId($cmd->snapshot);

        $snapshot->resync();

        return $snapshot;
    }
}
