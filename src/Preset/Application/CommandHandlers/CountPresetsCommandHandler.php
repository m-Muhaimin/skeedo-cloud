<?php

declare(strict_types=1);

namespace Preset\Application\CommandHandlers;

use Preset\Application\Commands\CountPresetsCommand;
use Preset\Domain\Repositories\PresetRepositoryInterface;

class CountPresetsCommandHandler
{
    public function __construct(
        private PresetRepositoryInterface $repo
    ) {}

    public function handle(CountPresetsCommand $cmd): int
    {
        $presets = $this->repo;

        if ($cmd->status) {
            $presets = $presets->filterByStatus($cmd->status);
        }

        if ($cmd->type) {
            $presets = $presets->filterByType($cmd->type);
        }

        if (is_bool($cmd->isLocked)) {
            $presets = $presets->filterByLock($cmd->isLocked);
        }

        if ($cmd->category) {
            $presets = $presets->filterByCategory($cmd->category);
        }

        if ($cmd->query) {
            $presets = $presets->search($cmd->query);
        }

        if ($cmd->ids) {
            $presets = $presets->filterById($cmd->ids);
        }

        return $presets->count();
    }
}
