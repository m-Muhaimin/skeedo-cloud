<?php

declare(strict_types=1);

namespace Preset\Application\CommandHandlers;

use Preset\Application\Commands\DeletePresetCommand;
use Preset\Domain\Events\PresetDeletedEvent;
use Preset\Domain\Exceptions\LockedPresetException;
use Preset\Domain\Exceptions\PresetNotFoundException;
use Preset\Domain\Repositories\PresetRepositoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class DeletePresetCommandHandler
{
    public function __construct(
        private PresetRepositoryInterface $repo,
        private EventDispatcherInterface $dispatcher
    ) {
    }

    /**
     * @throws PresetNotFoundException
     * @throws LockedPresetException
     */
    public function handle(DeletePresetCommand $cmd): void
    {
        $preset = $this->repo->ofId($cmd->id);

        if ($preset->isLocked()) {
            throw new LockedPresetException();
        }

        // Delete the preset from the repository
        $this->repo->remove($preset);

        // Dispatch the preset deleted event
        $event = new PresetDeletedEvent($preset);
        $this->dispatcher->dispatch($event);
    }
}
