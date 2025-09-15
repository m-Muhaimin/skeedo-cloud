<?php

declare(strict_types=1);

namespace Billing\Application\CommandHandlers;

use Billing\Application\Commands\CountSubscriptionsCommand;
use Billing\Domain\Repositories\SubscriptionRepositoryInterface;

class CountSubscriptionsCommandHandler
{
    public function __construct(
        private SubscriptionRepositoryInterface $repo
    ) {
    }

    public function handle(CountSubscriptionsCommand $cmd): int
    {
        $subs = $this->repo;

        if ($cmd->status) {
            $subs = $subs->filterByStatus($cmd->status);
        }

        if ($cmd->workspace) {
            $subs = $subs->filterByWorkspace($cmd->workspace);
        }

        if ($cmd->plan) {
            $subs = $subs->filterByPlan($cmd->plan);
        }

        return $subs->count();
    }
}
