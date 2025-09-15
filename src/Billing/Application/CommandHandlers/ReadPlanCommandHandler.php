<?php

declare(strict_types=1);

namespace Billing\Application\CommandHandlers;

use Billing\Application\Commands\ReadPlanCommand;
use Billing\Domain\Entities\PlanEntity;
use Billing\Domain\Exceptions\PlanNotFoundException;
use Billing\Domain\Repositories\PlanRepositoryInterface;

class ReadPlanCommandHandler
{
    /**
     * @param PlanRepositoryInterface $repo
     * @return void
     */
    public function __construct(
        private PlanRepositoryInterface $repo,
    ) {
    }

    /**
     * @param ReadPlanCommand $cmd
     * @return PlanEntity
     * @throws PlanNotFoundException
     */
    public function handle(ReadPlanCommand $cmd): PlanEntity
    {
        return $this->repo->ofId($cmd->id);
    }
}
