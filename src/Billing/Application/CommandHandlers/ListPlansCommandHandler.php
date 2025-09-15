<?php

declare(strict_types=1);

namespace Billing\Application\CommandHandlers;

use Billing\Application\Commands\ListPlansCommand;
use Billing\Domain\Entities\PlanEntity;
use Billing\Domain\Exceptions\PlanNotFoundException;
use Billing\Domain\Repositories\PlanRepositoryInterface;
use Shared\Domain\ValueObjects\CursorDirection;
use Traversable;

class ListPlansCommandHandler
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
     * @param ListPlansCommand $cmd
     * @return Traversable<PlanEntity>
     * @throws PlanNotFoundException
     */
    public function handle(ListPlansCommand $cmd): Traversable
    {
        $cursor = $cmd->cursor ? $this->repo->ofId($cmd->cursor) : null;

        $plans = $this->repo
            ->sort($cmd->sortDirection, $cmd->sortParameter);

        if ($cmd->status) {
            $plans = $plans->filterByStatus($cmd->status);
        }

        if ($cmd->billingCycle) {
            $plans = $plans->filterByBillingCycle($cmd->billingCycle);
        }

        if ($cmd->query) {
            $plans = $plans->search($cmd->query);
        }

        if ($cmd->maxResults) {
            $plans = $plans->setMaxResults($cmd->maxResults);
        }

        if ($cursor) {
            if ($cmd->cursorDirection == CursorDirection::ENDING_BEFORE) {
                return $plans = $plans->endingBefore($cursor);
            }

            return $plans->startingAfter($cursor);
        }

        return $plans;
    }
}
