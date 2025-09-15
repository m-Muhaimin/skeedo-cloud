<?php

declare(strict_types=1);

namespace Option\Application\CommandHandlers;

use Option\Application\Commands\CreateOptionCommand;
use Option\Domain\Entities\OptionEntity;
use Option\Domain\Events\OptionCreatedEvent;
use Option\Domain\Exceptions\KeyTakenException;
use Option\Domain\Repositories\OptionRepositoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class CreateOptionCommandHandler
{
    public function __construct(
        private OptionRepositoryInterface $repo,
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    /**
     * @throws KeyTakenException
     */
    public function handle(CreateOptionCommand $cmd): OptionEntity
    {
        $option = new OptionEntity(
            $cmd->key,
            $cmd->value,
        );

        // Add the option to the repository
        $this->repo->add($option);

        // Dispatch the option created event
        $event = new OptionCreatedEvent($option);
        $this->dispatcher->dispatch($event);

        return $option;
    }
}
