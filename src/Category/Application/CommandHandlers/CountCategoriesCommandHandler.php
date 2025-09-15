<?php

declare(strict_types=1);

namespace Category\Application\CommandHandlers;

use Category\Application\Commands\CountCategoriesCommand;
use Category\Domain\Repositories\CategoryRepositoryInterface;

class CountCategoriesCommandHandler
{
    public function __construct(
        private CategoryRepositoryInterface $repo,
    ) {
    }

    public function handle(CountCategoriesCommand $cmd): int
    {
        $categories = $this->repo;

        if ($cmd->query) {
            $categories = $categories->search($cmd->query);
        }

        return $categories->count();
    }
}
