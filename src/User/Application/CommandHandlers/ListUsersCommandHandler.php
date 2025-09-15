<?php

declare(strict_types=1);

namespace User\Application\CommandHandlers;

use Shared\Domain\ValueObjects\CursorDirection;
use Traversable;
use User\Application\Commands\ListUsersCommand;
use User\Domain\Entities\UserEntity;
use User\Domain\Exceptions\UserNotFoundException;
use User\Domain\Repositories\UserRepositoryInterface;

class ListUsersCommandHandler
{
    public function __construct(
        private UserRepositoryInterface $repo
    ) {}

    /**
     * @return Traversable<UserEntity>
     * @throws UserNotFoundException
     */
    public function handle(ListUsersCommand $cmd): Traversable
    {
        $cursor = $cmd->cursor
            ? $this->repo->ofUniqueKey($cmd->cursor)
            : null;

        $users = $this->repo;

        if ($cmd->sortDirection) {
            $users = $users->sort($cmd->sortDirection, $cmd->sortParameter);
        }

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

        if ($cmd->maxResults) {
            $users = $users->setMaxResults($cmd->maxResults);
        }

        if ($cursor) {
            if ($cmd->cursorDirection == CursorDirection::ENDING_BEFORE) {
                return $users = $users->endingBefore($cursor);
            }

            return $users->startingAfter($cursor);
        }

        return $users;
    }
}
