<?php

declare(strict_types=1);

namespace Option\Application\CommandHandlers;

use Option\Application\Commands\UpdateOptionCommand;
use Option\Domain\Entities\OptionEntity;
use Option\Domain\Events\OptionUpdatedEvent;
use Option\Domain\Exceptions\OptionNotFoundException;
use Option\Domain\Repositories\OptionRepositoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class UpdateOptionCommandHandler
{
    public function __construct(
        private OptionRepositoryInterface $repo,
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    /**
     * @throws OptionNotFoundException
     */
    public function handle(UpdateOptionCommand $cmd): OptionEntity
    {
        $option = $this->repo->ofId($cmd->id);

        if ($cmd->value) {
            $option->setValue($cmd->value);
        }

        // Dispatch the option updated event
        $event = new OptionUpdatedEvent($option);
        $this->dispatcher->dispatch($event);

        return $option;
    }
}
