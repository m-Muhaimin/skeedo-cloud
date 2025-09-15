<?php

declare(strict_types=1);

namespace Option\Application\CommandHandlers;

use Option\Application\Commands\SaveOptionCommand;
use Option\Domain\Entities\OptionEntity;
use Option\Domain\Events\OptionCreatedEvent;
use Option\Domain\Events\OptionUpdatedEvent;
use Option\Domain\Exceptions\KeyTakenException;
use Option\Domain\Exceptions\OptionNotFoundException;
use Option\Domain\Repositories\OptionRepositoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class SaveOptionCommandHandler
{
    public function __construct(
        private OptionRepositoryInterface $repo,
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    /**
     * @throws KeyTakenException
     */
    public function handle(SaveOptionCommand $cmd): OptionEntity
    {
        try {
            // Check if the option already exists
            $option = $this->repo->ofKey($cmd->key);

            // Update the value of the option
            $option->setValue($cmd->value);

            // Create the event to be dispatched
            $event = new OptionUpdatedEvent($option);
        } catch (OptionNotFoundException) {
            // If the option does not exist, create a new one
            $option = new OptionEntity(
                $cmd->key,
                $cmd->value,
            );

            // Add the option to the repository
            $this->repo->add($option);

            // Create the event to be dispatched
            $event = new OptionCreatedEvent($option);
        }

        // Dispatch the event
        $this->dispatcher->dispatch($event);

        return $option;
    }
}
