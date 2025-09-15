<?php

declare(strict_types=1);

namespace User\Application\CommandHandlers;

use Psr\EventDispatcher\EventDispatcherInterface;
use User\Application\Commands\UpdatePasswordCommand;
use User\Domain\Entities\UserEntity;
use User\Domain\Events\UserPasswordUpdatedEvent;
use User\Domain\Exceptions\UserNotFoundException;
use User\Domain\Exceptions\InvalidPasswordException;
use User\Domain\Repositories\UserRepositoryInterface;

class UpdatePasswordCommandHandler
{
    public function __construct(
        private UserRepositoryInterface $repo,
        private EventDispatcherInterface $dispatcher
    ) {
    }

    /**
     * @throws UserNotFoundException
     * @throws InvalidPasswordException
     */
    public function handle(UpdatePasswordCommand $cmd): UserEntity
    {
        $user = $cmd->id instanceof UserEntity
            ? $cmd->id
            : $this->repo->ofUniqueKey($cmd->id);

        $user->updatePassword(
            $cmd->currentPassword,
            $cmd->newPassword
        );

        // Dispatch the user password updated event
        $event = new UserPasswordUpdatedEvent($user);
        $this->dispatcher->dispatch($event);

        return $user;
    }
}
