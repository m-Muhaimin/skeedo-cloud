<?php

declare(strict_types=1);

namespace Billing\Application\CommandHandlers;

use Billing\Application\Commands\EndSubscriptionCommand;
use Billing\Domain\Entities\SubscriptionEntity;
use Billing\Domain\Exceptions\NotSubscriptionPlanException;
use Billing\Domain\Exceptions\SubscriptionNotFoundException;
use Billing\Domain\Exceptions\PlanNotFoundException;
use Billing\Domain\Repositories\PlanRepositoryInterface;
use Billing\Domain\Repositories\SubscriptionRepositoryInterface;
use Easy\Container\Attributes\Inject;
use Shared\Domain\ValueObjects\Id;

class EndSubscriptionCommandHandler
{
    public function __construct(
        private SubscriptionRepositoryInterface $repo,
        private PlanRepositoryInterface $prepo,

        #[Inject('option.billing.fallback_plan')]
        private ?string $defaultPlanId = null,
    ) {}

    /**
     * @throws SubscriptionNotFoundException
     * @throws PlanNotFoundException
     */
    public function handle(EndSubscriptionCommand $cmd): void
    {
        $sub = $cmd->subscription instanceof Id
            ? $this->repo->ofId($cmd->subscription)
            : $cmd->subscription;

        $ws = $sub->getWorkspace();
        $sub->end();

        $activeSub = $ws->getSubscription();

        if (
            !$activeSub
            || (string) $activeSub->getId()->getValue() !== (string) $sub->getId()->getValue()
        ) {
            return;
        }

        $plan = null;
        $ws->removeSubscription();

        if ($this->defaultPlanId) {
            try {
                $plan = $this->prepo->ofId(new Id($this->defaultPlanId));
                $sub = new SubscriptionEntity(
                    $ws,
                    $plan->getSnapshot()
                );

                $ws->subscribe($sub);
            } catch (PlanNotFoundException | NotSubscriptionPlanException $th) {
                // Fallback plan not found, do nothing
            }
        }
    }
}
