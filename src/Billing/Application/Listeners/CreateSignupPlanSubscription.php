<?php

declare(strict_types=1);

namespace Billing\Application\Listeners;

use Billing\Application\Commands\CreateSubscriptionCommand;
use Billing\Domain\Exceptions\NotSubscriptionPlanException;
use Billing\Domain\Exceptions\PlanNotFoundException;
use Easy\Container\Attributes\Inject;
use Shared\Infrastructure\CommandBus\Dispatcher;
use User\Domain\Events\UserCreatedEvent;

class CreateSignupPlanSubscription
{
    public function __construct(
        private Dispatcher $dispatcher,

        #[Inject('option.billing.signup_plan')]
        private ?string $planId = null,

        #[Inject('option.billing.trial_period_days')]
        private ?int $trialPeriodDays = 0
    ) {}

    public function __invoke(UserCreatedEvent $event): void
    {
        if (!$this->planId) {
            return;
        }

        $user = $event->user;
        $ws = $user->getOwnedWorkspaces()[0];

        $cmd = new CreateSubscriptionCommand($ws, $this->planId);
        if ($this->trialPeriodDays > 0) {
            $cmd->setTrialPeriodDays($this->trialPeriodDays);
        }

        try {
            $this->dispatcher->dispatch($cmd);
        } catch (PlanNotFoundException | NotSubscriptionPlanException $th) {
            // Do nothing if the plan is not found
        }
    }
}
