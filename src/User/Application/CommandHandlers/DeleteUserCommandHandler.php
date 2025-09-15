<?php

declare(strict_types=1);

namespace User\Application\CommandHandlers;

use Psr\EventDispatcher\EventDispatcherInterface;
use User\Application\Commands\DeleteUserCommand;
use User\Domain\Events\UserDeletedEvent;
use User\Domain\Exceptions\UserNotFoundException;
use User\Domain\Repositories\UserRepositoryInterface;

class DeleteUserCommandHandler
{
    public function __construct(
        private UserRepositoryInterface $repo,
        private EventDispatcherInterface $dispatcher
    ) {
    }

    /**
     * @throws UserNotFoundException
     */
    public function handle(DeleteUserCommand $cmd): void
    {
        // Find the user
        $user = $this->repo->ofUniqueKey($cmd->id);

        // Delete the user from the repository
        $this->repo->remove($user);

        // Dispatch the user deleted event
        $event = new UserDeletedEvent($user);
        $this->dispatcher->dispatch($event);
    }
}
