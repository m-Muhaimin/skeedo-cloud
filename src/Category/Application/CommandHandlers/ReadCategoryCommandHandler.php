<?php

declare(strict_types=1);

namespace Category\Application\CommandHandlers;

use Category\Application\Commands\ReadCategoryCommand;
use Category\Domain\Entities\CategoryEntity;
use Category\Domain\Exceptions\CategoryNotFoundException;
use Category\Domain\Repositories\CategoryRepositoryInterface;

class ReadCategoryCommandHandler
{
    public function __construct(
        private CategoryRepositoryInterface $repo
    ) {
    }

    /**
     * @throws CategoryNotFoundException
     */
    public function handle(ReadCategoryCommand $cmd): CategoryEntity
    {
        $category = $this->repo->ofId($cmd->id);
        return $category;
    }
}
