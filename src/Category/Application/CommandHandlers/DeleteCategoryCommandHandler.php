<?php

declare(strict_types=1);

namespace Category\Application\CommandHandlers;

use Category\Application\Commands\DeleteCategoryCommand;
use Category\Domain\Events\CategoryDeletedEvent;
use Category\Domain\Exceptions\CategoryNotFoundException;
use Category\Domain\Repositories\CategoryRepositoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class DeleteCategoryCommandHandler
{
    public function __construct(
        private CategoryRepositoryInterface $repo,
        private EventDispatcherInterface $dispatcher
    ) {
    }

    /**
     * @throws CategoryNotFoundException
     */
    public function handle(DeleteCategoryCommand $cmd): void
    {
        $category = $this->repo->ofId($cmd->id);

        // Delete the category from the repository
        $this->repo->remove($category);

        // Dispatch the category deleted event
        $event = new CategoryDeletedEvent($category);
        $this->dispatcher->dispatch($event);
    }
}
