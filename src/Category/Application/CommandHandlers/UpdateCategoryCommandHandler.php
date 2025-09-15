<?php

declare(strict_types=1);

namespace Category\Application\CommandHandlers;

use Category\Application\Commands\UpdateCategoryCommand;
use Category\Domain\Entities\CategoryEntity;
use Category\Domain\Events\CategoryUpdatedEvent;
use Category\Domain\Exceptions\CategoryNotFoundException;
use Category\Domain\Repositories\CategoryRepositoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class UpdateCategoryCommandHandler
{
    public function __construct(
        private CategoryRepositoryInterface $repo,
        private EventDispatcherInterface $dispatcher
    ) {
    }

    /**
     * @throws CategoryNotFoundException
     */
    public function handle(UpdateCategoryCommand $cmd): CategoryEntity
    {
        $category = $this->repo->ofId($cmd->id);

        if ($cmd->title) {
            $category->setTitle($cmd->title);
        }

        // Dispatch the category updated event
        $event = new CategoryUpdatedEvent($category);
        $this->dispatcher->dispatch($event);

        return $category;
    }
}
