<?php

declare(strict_types=1);

namespace Plugin\Application\CommandHandlers;

use Plugin\Application\Commands\InitializePluginCommand;
use Plugin\Domain\Exceptions\PluginNotFoundException;
use Plugin\Domain\Hooks\InstallHookInterface;
use Plugin\Domain\PluginWrapper;
use Plugin\Domain\Repositories\PluginRepositoryInterface;

class InitializePluginCommandHandler
{
    public function __construct(
        private PluginRepositoryInterface $repo,
    ) {
    }

    /**
     * @throws PluginNotFoundException
     */
    public function handle(InitializePluginCommand $cmd): PluginWrapper
    {
        $pw = $this->repo->ofName($cmd->name);
        $ins = $pw->plugin;

        if ($ins instanceof InstallHookInterface) {
            $ins->install($pw->context);
        }

        return $pw;
    }
}
