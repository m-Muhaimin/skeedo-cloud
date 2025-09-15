<?php

declare(strict_types=1);

namespace User\Application\CommandHandlers;

use Psr\EventDispatcher\EventDispatcherInterface;
use User\Application\Commands\UpdateEmailCommand;
use User\Domain\Entities\UserEntity;
use User\Domain\Events\UserEmailUpdatedEvent;
use User\Domain\Exceptions\UserNotFoundException;
use User\Domain\Exceptions\InvalidPasswordException;
use User\Domain\Exceptions\EmailTakenException;
use User\Domain\Repositories\UserRepositoryInterface;

class UpdateEmailCommandHandler
{
    public function __construct(
        private UserRepositoryInterface $repo,
        private EventDispatcherInterface $dispatcher
    ) {
    }

    /**
     * @throws UserNotFoundException
     * @throws InvalidPasswordException
     * @throws EmailTakenException
     */
    public function handle(UpdateEmailCommand $cmd): UserEntity
    {
        $user = $cmd->id instanceof UserEntity
            ? $cmd->id
            : $this->repo->ofUniqueKey($cmd->id);

        if ($cmd->email->value === $user->getEmail()->value) {
            return $user;
        }

        $user->updateEmail(
            $cmd->email,
            $cmd->password
        );

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
