<?php

declare(strict_types=1);

namespace User\Application\CommandHandlers;

use Psr\EventDispatcher\EventDispatcherInterface;
use User\Application\Commands\RequestEmailVerificationCommand;
use User\Domain\Entities\UserEntity;
use User\Domain\Events\UserEmailUpdatedEvent;
use User\Domain\Exceptions\EmailTakenException;
use User\Domain\Exceptions\UserNotFoundException;
use User\Domain\Repositories\UserRepositoryInterface;

class RequestEmailVerificationCommandHandler
{
    public function __construct(
        private UserRepositoryInterface $repo,
        private EventDispatcherInterface $dispatcher
    ) {
    }

    /**
     * @throws UserNotFoundException
     * @throws EmailTakenException
     */
    public function handle(
        RequestEmailVerificationCommand $cmd
    ): UserEntity {
        $user = $cmd->id instanceof UserEntity
            ? $cmd->id
            : $this->repo->ofUniqueKey($cmd->id);

        $user->unverifyEmail();

        try {
            $otherUser = $this->repo->ofEmail($user->getEmail());

            if ($otherUser->getId() != $user->getId()) {
                throw new EmailTakenException($user->getEmail());
            }
        } catch (UserNotFoundException $th) {
            // Do nothing
        }

        // Dispatch the user email updated event
        $event = new UserEmailUpdatedEvent($user);
        $this->dispatcher->dispatch($event);

        return $user;
    }
}
