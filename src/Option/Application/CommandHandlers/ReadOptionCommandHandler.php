<?php

declare(strict_types=1);

namespace Option\Application\CommandHandlers;

use Option\Application\Commands\ReadOptionCommand;
use Option\Domain\Entities\OptionEntity;
use Option\Domain\Exceptions\OptionNotFoundException;
use Option\Domain\Repositories\OptionRepositoryInterface;

class ReadOptionCommandHandler
{
    public function __construct(
        private OptionRepositoryInterface $repo,
    ) {
    }

    /**
     * @throws OptionNotFoundException
     */
    public function handle(ReadOptionCommand $cmd): OptionEntity
    {
        return $this->repo->ofId($cmd->id);
    }
}
