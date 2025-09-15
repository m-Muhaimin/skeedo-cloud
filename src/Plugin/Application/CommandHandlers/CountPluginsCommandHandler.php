<?php

declare(strict_types=1);

namespace Plugin\Application\CommandHandlers;

use Plugin\Application\Commands\CountPluginsCommand;
use Plugin\Domain\Repositories\PluginRepositoryInterface;

class CountPluginsCommandHandler
{
    public function __construct(
        private PluginRepositoryInterface $repo
    ) {
    }

    public function handle(CountPluginsCommand $cmd): int
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

        return $plugins->count();
    }
}
