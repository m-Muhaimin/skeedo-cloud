<?php

declare(strict_types=1);

namespace Plugin\Application\CommandHandlers;

use Plugin\Application\Commands\ReadPluginCommand;
use Plugin\Domain\Exceptions\PluginNotFoundException;
use Plugin\Domain\PluginWrapper;
use Plugin\Domain\Repositories\PluginRepositoryInterface;

class ReadPluginCommandHandler
{
    public function __construct(
        private PluginRepositoryInterface $repo
    ) {
    }

    /**
     * @throws PluginNotFoundException
     */
    public function handle(ReadPluginCommand $cmd): PluginWrapper
    {
        return $this->repo->ofName($cmd->name);
    }
}
