<?php

declare(strict_types=1);

namespace Assistant\Application\CommandHandlers;

use Assistant\Application\Commands\DeleteDataUnitCommand;
use Assistant\Domain\Entities\AssistantEntity;
use Assistant\Domain\Exceptions\AssistantNotFoundException;
use Assistant\Domain\Repositories\AssistantRepositoryInterface;

class DeleteDataUnitCommandHandler
{
    public function __construct(
        private AssistantRepositoryInterface $repo,
    ) {}

    /**
     * @param DeleteDataUnitCommand $cmd
     * @return AssistantEntity
     * @throws AssistantNotFoundException
     */
    public function handle(DeleteDataUnitCommand $cmd): AssistantEntity
    {
        $assistant = $cmd->assistant instanceof AssistantEntity
            ? $cmd->assistant
            : $this->repo->ofId($cmd->assistant);

        $assistant->removeDataUnit($cmd->unit);

        return $assistant;
    }
}
