<?php

declare(strict_types=1);

namespace Assistant\Application\CommandHandlers;

use Assistant\Application\Commands\UpdateAssistantCommand;
use Assistant\Domain\Entities\AssistantEntity;
use Assistant\Domain\Exceptions\AssistantNotFoundException;
use Assistant\Domain\Repositories\AssistantRepositoryInterface;

class UpdateAssistantCommandHandler
{
    public function __construct(
        private AssistantRepositoryInterface $repo
    ) {}

    /**
     * @throws AssistantNotFoundException
     */
    public function handle(UpdateAssistantCommand $cmd): AssistantEntity
    {
        $assistant = $cmd->assistant instanceof AssistantEntity
            ? $cmd->assistant : $this->repo->ofId($cmd->assistant);

        if ($cmd->name) {
            $assistant->setName($cmd->name);
        }

        if ($cmd->expertise) {
            $assistant->setExpertise($cmd->expertise);
        }

        if ($cmd->description) {
            $assistant->setDescription($cmd->description);
        }

        if ($cmd->instructions) {
            $assistant->setInstructions($cmd->instructions);
        }

        if ($cmd->avatar) {
            $assistant->setAvatar($cmd->avatar);
        }

        if ($cmd->model) {
            $assistant->setModel($cmd->model);
        }

        if ($cmd->status) {
            $assistant->setStatus($cmd->status);
        }

        if ($cmd->before || $cmd->after) {
            $before = $cmd->before ? $this->repo->ofId($cmd->before) : null;
            $after = $cmd->after ? $this->repo->ofId($cmd->after) : null;
            $assistant->placeBetween($after, $before);
        }

        return $assistant;
    }
}
