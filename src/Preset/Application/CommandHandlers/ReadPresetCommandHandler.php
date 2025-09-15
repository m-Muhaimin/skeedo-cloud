<?php

declare(strict_types=1);

namespace Preset\Application\CommandHandlers;

use Preset\Application\Commands\ReadPresetCommand;
use Preset\Domain\Entities\PresetEntity;
use Preset\Domain\Exceptions\PresetNotFoundException;
use Preset\Domain\Repositories\PresetRepositoryInterface;

class ReadPresetCommandHandler
{
    public function __construct(
        private PresetRepositoryInterface $repo
    ) {
    }

    /**
     * @throws PresetNotFoundException
     */
    public function handle(ReadPresetCommand $cmd): PresetEntity
    {
        return $this->repo->ofId($cmd->id);;
    }
}
