<?php

declare(strict_types=1);

namespace Ai\Application\CommandHandlers;

use Ai\Application\Commands\CountLibraryItemsCommand;
use Ai\Domain\Repositories\LibraryItemRepositoryInterface;
use Ai\Domain\ValueObjects\Visibility;

class CountLibraryItemsCommandHandler
{
    public function __construct(
        private LibraryItemRepositoryInterface $repo,
    ) {}

    public function handle(CountLibraryItemsCommand $cmd): int
    {
        $items = $this->repo;

        if ($cmd->workspace) {
            $items = $items->filterByWorkspace($cmd->workspace);
        }

        if ($cmd->user) {
            $items = $items->filterByUser(
                $cmd->user,
                $cmd->workspace ?: Visibility::PRIVATE
            );
        }

        if ($cmd->state) {
            $items = $items->filterByState(...$cmd->state);
        }

        if ($cmd->type) {
            $items = $items->filterByType($cmd->type);
        }

        if ($cmd->model) {
            $items = $items->filterByModel($cmd->model);
        }

        if ($cmd->query) {
            $items = $items->search($cmd->query);
        }

        if ($cmd->createdAfter) {
            $items = $items->createdAfter($cmd->createdAfter);
        }

        if ($cmd->createdBefore) {
            $items = $items->createdBefore($cmd->createdBefore);
        }

        return $items->count();
    }
}
