<?php

declare(strict_types=1);

namespace Plugin\Application\CommandHandlers;

use Iterator;
use Plugin\Application\Commands\ListPluginsCommand;
use Plugin\Domain\PluginWrapper;
use Plugin\Domain\Repositories\PluginRepositoryInterface;

class ListPluginsCommandHandler
{
    public function __construct(
        private PluginRepositoryInterface $repo
    ) {
    }

    /**
     * @param ListPluginsCommand $cmd
     * @return Iterator<int,PluginWrapper>
     */
    public function handle(ListPluginsCommand $cmd): Iterator
    {
        $plugins = $this->repo;

        if ($cmd->type) {
            $plugins = $plugins->filterByType($cmd->type);
        }

        if ($cmd->status) {
            $plugins = $plugins->filterByStatus($cmd->status);
        }

        if ($cmd->query) {
            $plugins = $plugins->search($cmd->query);
        }

        return $plugins->getIterator();
    }
}
