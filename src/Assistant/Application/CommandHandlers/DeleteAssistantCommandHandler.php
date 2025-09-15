<?php

declare(strict_types=1);

namespace Assistant\Application\CommandHandlers;

use Assistant\Application\Commands\DeleteAssistantCommand;
use Assistant\Domain\Exceptions\AssistantNotFoundException;
use Assistant\Domain\Repositories\AssistantRepositoryInterface;

class DeleteAssistantCommandHandler
{
    public function __construct(
        private AssistantRepositoryInterface $repo
    ) {
    }

    /**
     * @throws AssistantNotFoundException
     */
    public function handle(DeleteAssistantCommand $cmd): void
    {
        $assistant = $this->repo->ofId($cmd->id);

        // Delete the assistant from the repository
        $this->repo->remove($assistant);
    }
}
