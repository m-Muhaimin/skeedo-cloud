<?php

declare(strict_types=1);

namespace Plugin\Application\CommandHandlers;

use Plugin\Application\Commands\UpdatePluginCommand;
use Plugin\Domain\Exceptions\PluginNotFoundException;
use Plugin\Domain\Hooks\ActivateHookInterface;
use Plugin\Domain\Hooks\DeactivateHookInterface;
use Plugin\Domain\PluginWrapper;
use Plugin\Domain\Repositories\PluginRepositoryInterface;
use Plugin\Domain\ValueObjects\Status;
use Shared\Infrastructure\CacheManager;

class UpdatePluginCommandHandler
{
    public function __construct(
        private PluginRepositoryInterface $repo,
        private CacheManager $cache
    ) {
    }

    /**
     * @throws PluginNotFoundException
     */
    public function handle(UpdatePluginCommand $cmd): PluginWrapper
    {
        $pw = $this->repo->ofName($cmd->name);

        $context = $pw->context;
        if ($context->getStatus() ==  $cmd->status) {
            return $pw;
        }

        $context->setStatus($cmd->status);
        $ins = $pw->plugin;

        if (
            $cmd->status == Status::ACTIVE
            && $ins instanceof ActivateHookInterface
        ) {
            $ins->activate($pw->context);
        } elseif (
            $cmd->status == Status::INACTIVE
            && $ins instanceof DeactivateHookInterface
        ) {
            $ins->deactivate($pw->context);
        }

        // Clear cache
        $this->cache->clearCache();

        return $pw;
    }
}
