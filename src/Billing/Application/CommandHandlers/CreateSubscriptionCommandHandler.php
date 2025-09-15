<?php

declare(strict_types=1);

namespace Billing\Application\CommandHandlers;

use Billing\Application\Commands\CancelSubscriptionCommand;
use Billing\Application\Commands\CreateSubscriptionCommand;
use Billing\Domain\Entities\SubscriptionEntity;
use Billing\Domain\Exceptions\NotSubscriptionPlanException;
use Billing\Domain\Exceptions\PlanNotFoundException;
use Billing\Domain\Repositories\PlanRepositoryInterface;
use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Workspace\Domain\Exceptions\WorkspaceNotFoundException;
use Workspace\Domain\Repositories\WorkspaceRepositoryInterface;

class CreateSubscriptionCommandHandler
{
    public function __construct(
        private WorkspaceRepositoryInterface $wrepo,
        private PlanRepositoryInterface $prepo,
        private Dispatcher $dispatcher
    ) {
    }

    /**
     * @throws WorkspaceNotFoundException
     * @throws PlanNotFoundException
     * @throws NotSubscriptionPlanException
     */
    public function handle(CreateSubscriptionCommand $cmd): SubscriptionEntity
    {
        $ws = $cmd->workspace instanceof Id
            ? $this->wrepo->ofId($cmd->workspace) : $cmd->workspace;

        $plan = $cmd->plan instanceof Id
            ? $this->prepo->ofId($cmd->plan) : $cmd->plan;

        $activeSub = $ws->getSubscription();

        $sub = new SubscriptionEntity(
            $ws,
            $plan->getSnapshot(),
            $cmd->trialPeriodDays
        );

        $ws->subscribe($sub);

        if ($activeSub) {
            $cancelCmd = new CancelSubscriptionCommand($activeSub);
            $this->dispatcher->dispatch($cancelCmd);
        }

        return $sub;
    }
}
