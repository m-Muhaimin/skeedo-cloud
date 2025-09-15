<?php

declare(strict_types=1);

namespace Billing\Application\CommandHandlers;

use Billing\Application\Commands\UpdatePlanCommand;
use Billing\Domain\Entities\PlanEntity;
use Billing\Domain\Events\PlanUpdatedEvent;
use Billing\Domain\Exceptions\PlanNotFoundException;
use Billing\Domain\Repositories\PlanRepositoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class UpdatePlanCommandHandler
{
    public function __construct(
        private PlanRepositoryInterface $repo,
        private EventDispatcherInterface $dispatcher,
    ) {}

    /**
     * @throws PlanNotFoundException
     */
    public function handle(UpdatePlanCommand $cmd): PlanEntity
    {
        $plan = $this->repo->ofId($cmd->id);

        if ($cmd->title) {
            $plan->setTitle($cmd->title);
        }

        if ($cmd->price) {
            $plan->setPrice($cmd->price);
        }

        if ($cmd->billingCycle) {
            $plan->setBillingCycle($cmd->billingCycle);
        }

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

        if ($cmd->updateSnapshots) {
            $plan->resyncSnapshots();
        }

        // Dispatch the plan updated event
        $event = new PlanUpdatedEvent($plan);
        $this->dispatcher->dispatch($event);

        return $plan;
    }
}
