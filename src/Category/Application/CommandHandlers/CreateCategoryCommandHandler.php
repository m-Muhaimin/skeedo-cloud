<?php

declare(strict_types=1);

namespace Category\Application\CommandHandlers;

use Category\Application\Commands\CreateCategoryCommand;
use Category\Domain\Entities\CategoryEntity;
use Category\Domain\Events\CategoryCreatedEvent;
use Category\Domain\Repositories\CategoryRepositoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class CreateCategoryCommandHandler
{
    public function __construct(
        private CategoryRepositoryInterface $repo,
        private EventDispatcherInterface $dispatcher
    ) {
    }

    public function handle(CreateCategoryCommand $cmd): CategoryEntity
    {
        $category = new CategoryEntity(
            title: $cmd->title
        );

        // Add entoty to repository
        $this->repo->add($category);

        // Dispatch the category created event
        $event = new CategoryCreatedEvent($category);
        $this->dispatcher->dispatch($event);

        return $category;
    }
}
