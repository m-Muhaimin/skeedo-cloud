<?php

declare(strict_types=1);

namespace User\Application\CommandHandlers;

use User\Application\Commands\CountUsersCommand;
use User\Domain\Repositories\UserRepositoryInterface;

class CountUsersCommandHandler
{
    public function __construct(
        private UserRepositoryInterface $repo
    ) {}

    public function handle(CountUsersCommand $cmd): int
    {
        $users = $this->repo;

        if ($cmd->status) {
            $users = $users->filterByStatus($cmd->status);
        }

        if ($cmd->role) {
            $users = $users->filterByRole($cmd->role);
        }

        if ($cmd->countryCode) {
            $users = $users->filterByCountryCode($cmd->countryCode);
        }

        if ($cmd->isEmailVerified) {
            $users = $users->filterByEmailVerificationStatus($cmd->isEmailVerified);
        }

        if ($cmd->ref) {
            $users = $users->filterByRef($cmd->ref);
        }

        if ($cmd->after) {
            $users = $users->createdAfter($cmd->after);
        }

        if ($cmd->before) {
            $users = $users->createdBefore($cmd->before);
        }

        if ($cmd->query) {
            $users = $users->search($cmd->query);
        }

        return $users->count();
    }
}
