<?php

declare(strict_types=1);

namespace Affiliate\Application\CommandHandlers;

use Affiliate\Application\Commands\CountPayoutsCommand;
use Affiliate\Domain\Repositories\PayoutRepositoryInterface;
use User\Domain\Entities\UserEntity;
use User\Domain\Repositories\UserRepositoryInterface;

class CountPayoutsCommandHandler
{
    public function __construct(
        private PayoutRepositoryInterface $repo,
        private UserRepositoryInterface $userRepo
    ) {}

    public function handle(CountPayoutsCommand $cmd): int
    {
        $payouts = $this->repo;

        if ($cmd->status) {
            $payouts = $payouts->filterByStatus($cmd->status);
        }

        if ($cmd->user) {
            $user = $cmd->user instanceof UserEntity
                ? $cmd->user : $this->userRepo->ofId($cmd->user);

            $payouts = $payouts->filterByUser($user);
        }

        return $payouts->count();
    }
}
