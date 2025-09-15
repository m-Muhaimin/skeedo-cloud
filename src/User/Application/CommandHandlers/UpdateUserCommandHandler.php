<?php

declare(strict_types=1);

namespace User\Application\CommandHandlers;

use Psr\EventDispatcher\EventDispatcherInterface;
use User\Application\Commands\UpdateUserCommand;
use User\Domain\Entities\UserEntity;
use User\Domain\Events\UserUpdatedEvent;
use User\Domain\Exceptions\UserNotFoundException;
use User\Domain\Repositories\UserRepositoryInterface;

class UpdateUserCommandHandler
{
    public function __construct(
        private UserRepositoryInterface $repo,
        private EventDispatcherInterface $dispatcher
    ) {}

    /**
     * @throws UserNotFoundException
     */
    public function handle(UpdateUserCommand $cmd): UserEntity
    {
        $user = $cmd->id instanceof UserEntity
            ? $cmd->id
            : $this->repo->ofUniqueKey($cmd->id);

        if ($cmd->firstName) {
            $user->setFirstName($cmd->firstName);
        }

        if ($cmd->lastName) {
            $user->setLastName($cmd->lastName);
        }

        if ($cmd->phoneNumber) {
            $user->setPhoneNumber($cmd->phoneNumber);
        }

        if ($cmd->language) {
            $user->setLanguage($cmd->language);
        }

        if ($cmd->role) {
            $user->setRole($cmd->role);
        }

        if ($cmd->status) {
            $user->setStatus($cmd->status);
        }

        if ($cmd->workspaceCap) {
            $user->setWorkspaceCap($cmd->workspaceCap);
        }

        if ($cmd->preferences) {
            $user->setPreferences($cmd->preferences);
        }

        // Dispatch the user updated event
        $event = new UserUpdatedEvent($user);
        $this->dispatcher->dispatch($event);

        return $user;
    }
}
