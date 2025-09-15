<?php

declare(strict_types=1);

namespace Billing\Application\CommandHandlers;

use Billing\Application\Commands\ReadPlanSnapshotCommand;
use Billing\Domain\Entities\PlanSnapshotEntity;
use Billing\Domain\Exceptions\PlanSnapshotNotFoundException;
use Billing\Domain\Repositories\PlanSnapshotRepositoryInterface;

class ReadPlanSnapshotCommandHandler
{
    public function __construct(
        private PlanSnapshotRepositoryInterface $repo,
    ) {
    }

    /**
     * @throws PlanSnapshotNotFoundException
     */
    public function handle(ReadPlanSnapshotCommand $cmd): PlanSnapshotEntity
    {
        return $this->repo->ofId($cmd->id);
    }
}
