<?php

declare(strict_types=1);

namespace Assistant\Application\CommandHandlers;

use Assistant\Application\Commands\ReadAssistantCommand;
use Assistant\Domain\Entities\AssistantEntity;
use Assistant\Domain\Exceptions\AssistantNotFoundException;
use Assistant\Domain\Repositories\AssistantRepositoryInterface;

class ReadAssistantCommandHandler
{
    public function __construct(
        private AssistantRepositoryInterface $repo
    ) {
    }

    /**
     * @throws AssistantNotFoundException
     */
    public function handle(ReadAssistantCommand $cmd): AssistantEntity
    {
        return $this->repo->ofId($cmd->id);
    }
}
