<?php

declare(strict_types=1);

namespace User\Application\CommandHandlers;

use Psr\EventDispatcher\EventDispatcherInterface;
use User\Application\Commands\ResetPasswordCommand;
use User\Domain\Entities\UserEntity;
use User\Domain\Events\UserPasswordUpdatedEvent;
use User\Domain\Exceptions\UserNotFoundException;
use User\Domain\Exceptions\InvalidTokenException;
use User\Domain\Repositories\UserRepositoryInterface;

class ResetPasswordCommandHandler
{
    public function __construct(
        private UserRepositoryInterface $repo,
        private EventDispatcherInterface $dispatcher
    ) {
    }

    /**
     * @throws UserNotFoundException
     * @throws InvalidTokenException
     */
    public function handle(ResetPasswordCommand $cmd): UserEntity
    {
        $user = $cmd->id instanceof UserEntity
            ? $cmd->id
            : $this->repo->ofUniqueKey($cmd->id);

        $user->resetPassword($cmd->token, $cmd->newPassword);

        // Dispatch the user password updated event
        $event = new UserPasswordUpdatedEvent($user);
        $this->dispatcher->dispatch($event);

        return $user;
    }
}
