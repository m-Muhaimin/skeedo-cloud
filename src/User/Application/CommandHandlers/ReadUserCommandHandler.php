<?php

declare(strict_types=1);

namespace User\Application\CommandHandlers;

use User\Application\Commands\ReadUserCommand;
use User\Domain\Entities\UserEntity;
use User\Domain\Exceptions\UserNotFoundException;
use User\Domain\Repositories\UserRepositoryInterface;

class ReadUserCommandHandler
{
    public function __construct(
        private UserRepositoryInterface $repo,
    ) {
    }

    /**
     * @throws UserNotFoundException
     */
    public function handle(ReadUserCommand $cmd): UserEntity
    {
        return $this->repo->ofUniqueKey($cmd->id);
    }
}
