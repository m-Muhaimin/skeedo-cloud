<?php

declare(strict_types=1);

namespace User\Application\CommandHandlers;

use Psr\EventDispatcher\EventDispatcherInterface;
use User\Application\Commands\GenerateApiKeyCommand;
use User\Domain\Entities\UserEntity;
use User\Domain\Exceptions\UserNotFoundException;
use User\Domain\Repositories\UserRepositoryInterface;

class GenerateApiKeyCommandHandler
{
    public function __construct(
        private UserRepositoryInterface $repo,
        private EventDispatcherInterface $dispatcher
    ) {
    }

    /**
     * @throws UserNotFoundException
     */
    public function handle(GenerateApiKeyCommand $cmd): UserEntity
    {
        $user = $cmd->id instanceof UserEntity
            ? $cmd->id
            : $this->repo->ofUniqueKey($cmd->id);

        $user->generateApiKey();

        return $user;
    }
}
