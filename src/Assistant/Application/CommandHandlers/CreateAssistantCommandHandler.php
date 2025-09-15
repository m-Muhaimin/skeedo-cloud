<?php

declare(strict_types=1);

namespace Assistant\Application\CommandHandlers;

use Assistant\Application\Commands\CreateAssistantCommand;
use Assistant\Domain\Entities\AssistantEntity;
use Assistant\Domain\Repositories\AssistantRepositoryInterface;
use Assistant\Domain\ValueObjects\SortParameter;
use Shared\Domain\ValueObjects\MaxResults;
use Shared\Domain\ValueObjects\SortDirection;

class CreateAssistantCommandHandler
{
    public function __construct(
        private AssistantRepositoryInterface $repo
    ) {}

    public function handle(CreateAssistantCommand $cmd): AssistantEntity
    {
        $assistant = new AssistantEntity($cmd->name);

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

        $first = $this->repo->sort(SortDirection::ASC, SortParameter::POSITION)
            ->setMaxResults(new MaxResults(1))
            ->getIterator()
            ->current();

        if ($first) {
            $assistant->placeBetween(null, $first);
        }

        $this->repo->add($assistant);
        return $assistant;
    }
}
