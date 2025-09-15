<?php

declare(strict_types=1);

namespace Billing\Application\CommandHandlers;

use Billing\Application\Commands\CreatePlanCommand;
use Billing\Domain\Entities\PlanEntity;
use Billing\Domain\Events\PlanCreatedEvent;
use Billing\Domain\Repositories\PlanRepositoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class CreatePlanCommandHandler
{
    /**
     * @param PlanRepositoryInterface $repo
     * @param EventDispatcherInterface $dispatcher
     * @return void
     */
    public function __construct(
        private PlanRepositoryInterface $repo,
        private EventDispatcherInterface $dispatcher,
    ) {}

    /**
     * @param CreatePlanCommand $cmd
     * @return PlanEntity
     */
    public function handle(CreatePlanCommand $cmd): PlanEntity
    {
        $plan = new PlanEntity(
            $cmd->title,
            $cmd->price,
            $cmd->billingCycle
        );

        if ($cmd->description) {
            $plan->setDescription($cmd->description);
        }

        if ($cmd->creditCount) {
            $plan->setCreditCount($cmd->creditCount);
        }

        if ($cmd->superiority) {
            $plan->setSuperiority($cmd->superiority);
        }

        if ($cmd->status) {
            $plan->setStatus($cmd->status);
        }

        if ($cmd->isFeatured) {
            $plan->setIsFeatured($cmd->isFeatured);
        }

        if ($cmd->icon) {
            $plan->setIcon($cmd->icon);
        }

        if ($cmd->featureList) {
            $plan->setFeatureList($cmd->featureList);
        }

        if ($cmd->config) {
            $plan->setConfig($cmd->config);
        }

        if ($cmd->memberCap) {
            $plan->setMemberCap($cmd->memberCap);
        }

        // Add the plan to the repository
        $this->repo->add($plan);

        // Dispatch the plan created event
        $event = new PlanCreatedEvent($plan);
        $this->dispatcher->dispatch($event);

        return $plan;
    }
}
