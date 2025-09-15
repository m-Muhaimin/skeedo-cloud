<?php

declare(strict_types=1);

namespace User\Application\CommandHandlers;

use User\Application\Commands\BasicAuthCommand;
use User\Domain\Entities\UserEntity;
use User\Domain\Exceptions\UserNotFoundException;
use User\Domain\Exceptions\InvalidPasswordException;
use User\Domain\Repositories\UserRepositoryInterface;

class BasicAuthCommandHandler
{
    public function __construct(
        private UserRepositoryInterface $repo,
    ) {
    }

    /**
     * @throws UserNotFoundException
     * @throws InvalidPasswordException
     */
    public function handle(BasicAuthCommand $cmd): UserEntity
    {
        $user = $this->repo->ofEmail($cmd->email);
        $user->verifyPassword($cmd->password);

        return $user;
    }
}
