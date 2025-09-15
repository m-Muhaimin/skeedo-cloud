<?php

declare(strict_types=1);

namespace User\Application\CommandHandlers;

use Psr\EventDispatcher\EventDispatcherInterface;
use User\Application\Commands\VerifyEmailCommand;
use User\Domain\Entities\UserEntity;
use User\Domain\Events\EmailVerifiedEvent;
use User\Domain\Exceptions\UserNotFoundException;
use User\Domain\Exceptions\InvalidTokenException;
use User\Domain\Repositories\UserRepositoryInterface;

class VerifyEmailCommandHandler
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
    public function handle(VerifyEmailCommand $cmd): UserEntity
    {
        $user =  $cmd->id instanceof UserEntity
            ? $cmd->id
            : $this->repo->ofUniqueKey($cmd->id);

        if ($user->isEmailVerified()->value) {
            return $user;
        }

        $user->verifyEmail($cmd->token);

        // Dispatch the user updated event
        $event = new EmailVerifiedEvent($user);
        $this->dispatcher->dispatch($event);

        return $user;
    }
}
